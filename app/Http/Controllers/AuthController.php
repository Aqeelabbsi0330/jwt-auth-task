<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RefreshToken;
use App\Models\BlacklistedToken;
use App\Models\BlacklistedRefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\UserToken;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function refreshToken(Request $request)
    {
        try {

            $refreshToken = $request->cookie('refresh_token') ?? $request->input('refresh_token');
            if (!$refreshToken) {
                return response()->json(['error' => 'Refresh token not provided'], 401);
            }
            try {
                $payload = JWT::decode($refreshToken, new Key(env('JWT_SECRET'), 'HS256'));
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid refresh token payload'], 401);
            }
            // Find matching refresh token
            $matchedRefresh = null;

            foreach (RefreshToken::where('user_id', $payload->sub)->get() as $r) {
                if (Hash::check($refreshToken, $r->refresh_token)) {
                    $matchedRefresh = $r;
                    break;
                }
            }

            if (!$matchedRefresh) {
                return response()->json(['error' => 'Invalid refresh token'], 401);
            }

            if ($matchedRefresh->expire_at < now()) {
                return response()->json(['error' => 'Refresh token expired'], 401);
            }

            // Decode old refresh token

            // Check blacklist
            if (BlacklistedRefreshToken::where('jti', $payload->jti)->exists()) {
                return response()->json(['error' => 'Refresh token is blacklisted'], 401);
            }

            // Get user
            $user = User::find($matchedRefresh->user_id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Create new access token
            $newPayload = [
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'iat' => time(),
                'exp' => time() + 3600,
                'jti' => (string) Str::uuid(),
            ];
            $newAccessToken = JWT::encode($newPayload, env('JWT_SECRET'), 'HS256');
            // Save access token in DB
            $userToken = new UserToken();
            $userToken->user_id = $user->id;
            $userToken->token = $newAccessToken;
            $userToken->jti = $newPayload['jti'];
            $userToken->device_type = $request->input('device_type', 'web');
            $userToken->ip_address = $request->ip();
            $userToken->expire_at = Carbon::createFromTimestamp($newPayload['exp']);
            $userToken->created_by = $user->id;
            $userToken->updated_by = $user->id;
            $userToken->save();

            // Blacklist old refresh token
            $blacklistedRefreshToken = new BlacklistedRefreshToken();
            $blacklistedRefreshToken->user_id = $payload->sub;
            $blacklistedRefreshToken->refresh_token = Hash::make($refreshToken); // 🔑 hashed store
            $blacklistedRefreshToken->jti = $payload->jti;
            $blacklistedRefreshToken->expire_at = Carbon::createFromTimestamp($payload->exp);
            $blacklistedRefreshToken->save();

            // Delete old refresh token
            $matchedRefresh->delete();

            // Create new refresh token
            $newRefreshPayload = [
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'iat' => time(),
                'exp' => Carbon::now()->addDays(30)->timestamp,
                'jti' => (string) Str::uuid()
            ];
            $newRefreshToken = JWT::encode($newRefreshPayload, env('JWT_SECRET'), 'HS256');
            $hashNewRefreshToken = Hash::make($newRefreshToken);

            $newRefreshTokenModel = new RefreshToken();
            $newRefreshTokenModel->user_id = $user->id;
            $newRefreshTokenModel->refresh_token = $hashNewRefreshToken;
            $newRefreshTokenModel->jti = $newRefreshPayload['jti'];
            $newRefreshTokenModel->device_type = $request->input('device_type', 'web');
            $newRefreshTokenModel->ip_address = $request->ip();
            $newRefreshTokenModel->expire_at = Carbon::createFromTimestamp($newRefreshPayload['exp']);
            $newRefreshTokenModel->created_by = $user->id;
            $newRefreshTokenModel->updated_by = $user->id;
            $newRefreshTokenModel->save();

            return response()->json([
                'new access_token' => $newAccessToken,
                'new refresh_token' => $newRefreshToken,
                'new token_expire_at' => $userToken->expire_at,
                'new refresh_token_expire_at' => $newRefreshTokenModel->expire_at,
            ])->cookie(
                'refresh_token',
                $newRefreshToken,
                60 * 24 * 30,
                '/',
                null,
                false,
                true
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ]);
        }
    }
}
