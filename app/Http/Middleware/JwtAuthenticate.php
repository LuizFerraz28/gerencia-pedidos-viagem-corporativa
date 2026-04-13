<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 401);
            }
        } catch (TokenExpiredException) {
            return response()->json(['message' => 'Token expirado.'], 401);
        } catch (JWTException) {
            return response()->json(['message' => 'Token inválido ou ausente.'], 401);
        }

        return $next($request);
    }
}
