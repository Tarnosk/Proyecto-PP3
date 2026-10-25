<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use App\Modules\Login\Services\ApiTokenService;
use App\Support\UsuarioActual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de identidad (integrado con App\Modules\Login de G1).
 *
 * Resuelve el usuario autenticado desde el módulo Login (tabla usuarios / modelo User)
 * o token de servicio interno, y lo expone en el atributo de request 'usuario_autenticado_id'
 * consumido por UsuarioActual::id().
 */
class IdentidadUsuario
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $this->idDisponible($request);

        if ($id !== null) {
            $request->attributes->set(UsuarioActual::ATTRIBUTO, $id);
        }

        return $next($request);
    }

    private function idDisponible(Request $request): ?int
    {
        // 1. Token Bearer (ApiTokenService de G1 / llamadas autenticadas entre módulos)
        $bearer = $request->bearerToken();
        if (! empty($bearer)) {
            $idToken = $this->resolverTokenG1($bearer);
            if ($idToken !== null) {
                return $idToken;
            }
        }

        // 2. Guard de Laravel configurado ('proyecto' o guard web por defecto de G1)
        $guard = config('auth.guards.proyecto') !== null ? Auth::guard('proyecto') : null;
        if ($guard !== null && $guard->check()) {
            return (int) $guard->id();
        }

        if (Auth::check()) {
            return (int) Auth::id();
        }

        // 3. Encabezado directo de ID de usuario (compatibilidad de pruebas internas)
        $headerId = $request->header('X-User-Id');
        if (! empty($headerId) && is_numeric($headerId) && (int) $headerId > 0) {
            return (int) $headerId;
        }

        // 4. Búsqueda de sólo lectura por nombre de usuario en headers (sin aprovisionar en GET)
        $headerUsername = $request->header('X-User-Username');
        $headerNameRaw = $request->header('X-User-Name');
        $headerName = $headerNameRaw ? urldecode($headerNameRaw) : null;

        if (! empty($headerUsername) || ! empty($headerName)) {
            $resolvedId = $this->buscarUsuarioExistente($headerUsername, $headerName);
            if ($resolvedId !== null) {
                return $resolvedId;
            }
        }

        return null;
    }

    /**
     * Resuelve un token emitido por el módulo de autenticación (App\Modules\Login de G1).
     */
    private function resolverTokenG1(?string $token): ?int
    {
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        try {
            // A) Token interno de servicio inter-módulos
            $internal = config('services.internal_api.token');
            if (is_string($internal) && $internal !== '' && hash_equals($internal, $token)) {
                return 1;
            }

            // B) Token emitido y validado por ApiTokenService (G1)
            $apiTokenService = app(ApiTokenService::class);
            $authUser = $apiTokenService->resolve($token);
            if ($authUser !== null) {
                return (int) $authUser->id;
            }

            // C) Token registrado en sesión activa de tabla usuarios (G1)
            $hashToken = hash('sha256', $token);
            if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'current_session_id')) {
                $userId = User::query()
                    ->where('current_session_id', $hashToken)
                    ->where('estado', 'activo')
                    ->value('id');
                if ($userId !== null) {
                    return (int) $userId;
                }
            }
        } catch (\Throwable $e) {
            // Silencioso: si el token no es válido, no autentica
        }

        return null;
    }

    private function buscarUsuarioExistente(?string $username, ?string $nombre): ?int
    {
        try {
            if (Schema::hasTable('usuarios')) {
                $query = User::query();
                if (! empty($username)) {
                    $query->where('usuario', $username);
                } elseif (! empty($nombre)) {
                    $query->where('nombre', $nombre);
                }
                $id = $query->value('id');
                if ($id !== null) {
                    return (int) $id;
                }
            }

            if (Schema::hasTable('usuario')) {
                $columnas = Schema::getColumnListing('usuario');
                $campoUsuario = in_array('user', $columnas, true) ? 'user' : 'usuario';
                $query = DB::table('usuario');
                if (! empty($username)) {
                    $query->where($campoUsuario, $username);
                } elseif (! empty($nombre) && in_array('nombre', $columnas, true)) {
                    $query->where('nombre', $nombre);
                }
                $id = $query->value('id_usuario');
                if ($id !== null) {
                    return (int) $id;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
