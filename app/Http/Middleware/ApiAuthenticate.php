<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $apiToken = ApiToken::findByPlainToken($token);

        if (!$apiToken) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        if ($apiToken->isExpired()) {
            return response()->json(['message' => 'Token expired.'], 401);
        }

        $user = $apiToken->user;

        if (!$user || !$user->is_active) {
            return response()->json(['message' => 'User inactive or not found.'], 401);
        }

        $apiToken->update(['last_used_at' => now()]);

        Auth::login($user);

        // Force JSON response when Accept header is present
        if (!$request->headers->has('Accept') || !str_contains($request->header('Accept'), 'application/json')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
