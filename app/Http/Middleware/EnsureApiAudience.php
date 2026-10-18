<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAudience
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if ($request->attributes->get('api_service') === true
            || in_array($request->user()?->rol?->value, $roles, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'No tiene permisos para esta operacion.'], 403);
    }
}
