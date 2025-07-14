<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpireSanctumToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        $expiration = config('sanctum.expiration');

        if ($token && $expiration && $token->created_at->addMinutes($expiration)->isPast()) {
            $token->delete(); // opcional: revoga o token
            return response()->json(['message' => 'Token expirado'], 401);
        }

        return $next($request);
    }
}
