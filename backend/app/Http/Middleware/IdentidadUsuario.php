<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Support\UsuarioActual;

/**
 * Middleware de identidad (contrato con G1 - autenticación).
 *
 * G1 autentica (login, token o sesión) y este middleware traduce esa
 * identidad a un atributo confiable de request ('usuario_autenticado_id'),
 * que los módulos leen via UsuarioActual::id(). Si G1 usa un guard de
 * Laravel contra la tabla USUARIO, se detecta aquí automáticamente
 * (guard 'proyecto' o el guard por defecto).
 *
 * Mientras G1 no integre el login no hay identidad marcada y
 * UsuarioActual cae al fallback admin (id=1): placeholder explícito.
 */
class IdentidadUsuario
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $this->idDisponible();

        if ($id !== null) {
            $request->attributes->set(UsuarioActual::ATTRIBUTO, $id);
        }

        return $next($request);
    }

    private function idDisponible(): ?int
    {
        $guard = config('auth.guards.proyecto') !== null ? Auth::guard('proyecto') : null;

        if ($guard !== null && $guard->check()) {
            return (int) $guard->id();
        }

        if (Auth::check()) {
            return (int) Auth::id();
        }

        return null;
    }
}