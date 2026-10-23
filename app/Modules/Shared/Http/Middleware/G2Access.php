<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Auth\G2Identity;
use Closure;
use Illuminate\Http\Request;

class G2Access
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! in_array(G2Identity::role(), $roles, true)) {
            return $request->expectsJson()
                ? response()->json(['error' => true, 'mensaje' => 'No tenés permisos para esta operación.'], 403)
                : redirect()->route('dashboard')->with('permission_error', 'No tenés permisos para esta pantalla.');
        }

        return $next($request);
    }
}
