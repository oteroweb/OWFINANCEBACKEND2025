<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        if (! $user || ! $user->role || $user->role->slug !== $role) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }
        return $next($request);
    }
}
