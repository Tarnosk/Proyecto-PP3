<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->rol?->value;

        if (! in_array($role, $roles, true)) {
            return redirect()->route('dashboard')
                ->with('permission_error', 'No tiene permisos para acceder a esa pantalla.');
        }

        return $next($request);
    }
}
