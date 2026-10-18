<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUser
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $user = $request->user();

        if (! $user || ($role !== null && $user->rol->value !== $role)) {
            return response()->json(['message' => 'No tiene permisos para esta operacion.'], 403);
        }

        return $next($request);
    }
}
