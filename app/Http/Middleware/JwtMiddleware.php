<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\BlacklistedToken;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {

            $token = $request->bearerToken();
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            $user = JWTAuth::setToken($token)->authenticate();
            if (!$user) {
                return response()->json(['error' => 'Invalid token'], 401);
            }
            $payload = JWTAuth::setToken($token)->getPayload();
            $jti = $payload->get('jti');
            $blacklisted = BlacklistedToken::where('jti', $jti)->first();
            if ($blacklisted) {
                return response()->json(['error' => 'Token is blacklisted'], 401);
            }
            $request->attributes->set('user', $user);
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Token error',
                'message' => $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ], 401);
        }
    }
}
