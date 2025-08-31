<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use App\Models\BlacklistedToken;
use App\Models\RefreshToken;
use App\Models\BlacklistedRefreshToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class JwtController extends Controller
{
    public function register(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            response()->json(['message' => 'User successfully registered', 'user' => $user], 201);
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Registration failed', 'message' =>
                $e->getMessage(), $e->getfile(), $e->getLine()],
                500
            );
        }
    }

    public function login(Request $request)
    {
        try {


            $credentials = $request->post('email', 'password');
            if (!$credentials) {
                return response()->json(['error' => 'Invalid Credentials'], 401);
            }
            $user = User::where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Invalid Credentials'], 401);
            }
            // jwt contain the payload secrete key and alog
            $payload = [
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'iat' => time(),
                'exp' => time() + 900, //valid for 15 minutes
                'jti' => (string) Str::uuid(),

            ];
            $secret_key = env('JWT_SECRET');
            $token = JWT::encode($payload, $secret_key, 'HS256');
            //refresh token
            $refreshPayload = [
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'iat' => time(),
                'exp' => Carbon::now()->addDays(30)->timestamp,
                'jti' => (string) Str::uuid()

            ];
            $refreshToken = JWT::encode($refreshPayload, $secret_key, 'HS256');
            $hashRefreshToken = Hash::make($refreshToken);
            $refreshTokenModel = new RefreshToken();
            $refreshTokenModel->user_id = $user->id;
            $refreshTokenModel->refresh_token = $hashRefreshToken;
            $refreshTokenModel->jti = $refreshPayload['jti'];
            $refreshTokenModel->device_type = $request->input('device_type', 'web');
            $refreshTokenModel->ip_address = $request->ip();
            $refreshTokenModel->expire_at = Carbon::createFromTimestamp($refreshPayload['exp']);
            $userToken = new UserToken();
            $userToken->user_id = $user->id;
            $userToken->token = $token;
            $userToken->jti = $payload['jti'];
            $userToken->device_type = $request->input('device_type', 'web');
            $userToken->ip_address = $request->ip();
            $userToken->expire_at = Carbon::createFromTimestamp($payload['exp']);
            $userToken->created_by = $user->id;
            $userToken->updated_by = $user->id;
            $userToken->save();
            $refreshTokenModel->save();
            $data = [
                'user' => [
                    'token' => $token,
                    'refresh_token' => $refreshToken,
                    'token_expire_at' => $userToken->expire_at,
                    'refresh_token_expire_at' => $refreshTokenModel->expire_at,

                ]
            ];
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => $data
            ])->cookie(
                'refresh_token',
                $refreshToken,
                60 * 24 * 30,
                '/',
                null,
                false,
                true,
                false,
                'None'
            );
        } catch (\Exception $e) {
            return response()->json(
                ['error' => 'Login failed', 'message' =>
                $e->getMessage(), $e->getfile(), $e->getLine()],
                500
            );
        }
    }
    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'please login '
                ]);
            }
            $userToken = UserToken::where('token', $token)->first();
            if (!$userToken) {
                return response()->json(['error' => 'Invalid token'], 401);
            }
            $user_id = $userToken->user_id;
            $refreshToken = RefreshToken::where('user_id', $user_id)->get();
            $getRefreshToken = null;
            foreach ($refreshToken as $r) {
                if (Hash::check($refreshToken, $r->refresh_token));
                $getRefreshToken = $r;
                break;
            }
            // print_r($getRefreshToken);
            BlacklistedToken::create([
                'jti' => $userToken->jti ?? null,   // JWT ID
                'token' => $token,
                'user_id' => $userToken->user_id,
                'expire_at' => $userToken->expire_at,
            ]);
            $userToken->forceDelete();
            BlacklistedRefreshToken::Create(
                [
                    'jti' => $getRefreshToken->jti,
                    'refresh_token' => $getRefreshToken->refresh_token,
                    'user_id' => $getRefreshToken->user_id,
                    'expire_at' => $getRefreshToken->expire_at
                ]
            );
            $getRefreshToken->forceDelete();
            return response()->json(['message' => 'Logout successful'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
}
