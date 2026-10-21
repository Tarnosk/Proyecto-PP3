<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Services\ApiTokenService;
use App\Support\UsuarioActual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

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
 * UsuarioActual cae al fallback de usuario administrativo activo.
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
        // 1. Token Bearer (G1 / llamadas autenticadas entre módulos)
        $bearer = $request->bearerToken();
        if (! empty($bearer)) {
            $idToken = $this->resolverTokenG1($bearer);
            if ($idToken !== null) {
                return $idToken;
            }
        }

        // 2. Encabezado directo de ID de usuario
        $headerId = $request->header('X-User-Id');
        if (! empty($headerId) && is_numeric($headerId) && (int) $headerId > 0) {
            return (int) $headerId;
        }

        // 3. Identidad enviada desde el frontend SPA / cliente HTTP
        $headerUsername = $request->header('X-User-Username');
        $headerNameRaw = $request->header('X-User-Name');
        $headerName = $headerNameRaw ? urldecode($headerNameRaw) : null;
        $headerRole = $request->header('X-User-Role');

        if (! empty($headerUsername) || ! empty($headerName)) {
            $resolvedId = $this->resolverOCrearUsuario($headerUsername, $headerName, $headerRole);
            if ($resolvedId !== null) {
                return $resolvedId;
            }
        }

        // 4. Guard de Laravel configurado
        $guard = config('auth.guards.proyecto') !== null ? Auth::guard('proyecto') : null;

        if ($guard !== null && $guard->check()) {
            return (int) $guard->id();
        }

        if (Auth::check()) {
            $user = Auth::user();
            if ($user && ! empty($user->usuario)) {
                $rolStr = $user->rol instanceof UserRole ? $user->rol->value : (string) ($user->rol ?? 'administrativo');
                $resolvedId = $this->resolverOCrearUsuario($user->usuario, $user->nombre ?? null, $rolStr);
                if ($resolvedId !== null) {
                    return $resolvedId;
                }
            }

            return (int) Auth::id();
        }

        return null;
    }

    /**
     * Resuelve un token emitido por el módulo de autenticación (Grupo 1).
     * Soporta:
     * - Token interno de servicio entre grupos (services.internal_api.token).
     * - Token de sesión registrado en tabla usuario (current_session_id sha256).
     * - Token cifrado de G1 con payload JSON (user_id + expires_at).
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

            // B) Token emitido por ApiTokenService (G1)
            try {
                $apiTokenService = app(ApiTokenService::class);
                $authUser = $apiTokenService->resolve($token);
                if ($authUser !== null) {
                    $rolStr = $authUser->rol instanceof UserRole ? $authUser->rol->value : (string) ($authUser->rol ?? 'administrativo');
                    $legacyId = $this->resolverOCrearUsuario($authUser->usuario, $authUser->nombre, $rolStr);
                    if ($legacyId !== null) {
                        return $legacyId;
                    }

                    return (int) $authUser->id;
                }
            } catch (\Throwable $e) {
            }

            // C) Token registrado en sesión activa de usuario (tabla usuarios o usuario)
            $hashToken = hash('sha256', $token);
            if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'current_session_id')) {
                $userByToken = DB::table('usuarios')->where('current_session_id', $hashToken)->where('estado', 'activo')->first();
                if ($userByToken) {
                    $legacyId = $this->resolverOCrearUsuario($userByToken->usuario, $userByToken->nombre ?? null, $userByToken->rol ?? 'administrativo');
                    if ($legacyId !== null) {
                        return $legacyId;
                    }

                    return (int) $userByToken->id;
                }
            }

            $tablaUsuario = Schema::hasTable('usuario') ? 'usuario' : (Schema::hasTable('users') ? 'users' : null);
            if ($tablaUsuario !== null && Schema::hasColumn($tablaUsuario, 'current_session_id')) {
                $campoId = Schema::hasColumn($tablaUsuario, 'id_usuario') ? 'id_usuario' : 'id';
                $usuarioPorSession = DB::table($tablaUsuario)
                    ->where('current_session_id', $hashToken)
                    ->where(function ($q) use ($tablaUsuario) {
                        if (Schema::hasColumn($tablaUsuario, 'estado')) {
                            $q->where('estado', 'activo');
                        }
                    })
                    ->value($campoId);

                if ($usuarioPorSession !== null) {
                    return (int) $usuarioPorSession;
                }
            }

            // D) Desencriptar payload de token si fue emitido con Crypt de Laravel
            $payloadJson = Crypt::decryptString($token);
            $payload = json_decode($payloadJson, true);

            if (is_array($payload) && isset($payload['user_id'])) {
                if (! isset($payload['expires_at']) || (int) $payload['expires_at'] > time()) {
                    return (int) $payload['user_id'];
                }
            }
        } catch (\Throwable $e) {
            // Silencioso: si el token no es compatible, continúa la cadena de resolución
        }

        return null;
    }

    private function resolverOCrearUsuario(?string $username, ?string $nombre, ?string $rol): ?int
    {
        try {
            $columnas = Schema::getConnection()->getSchemaBuilder()->getColumnListing('usuario');
            $nombreExiste = in_array('nombre', $columnas, true);
            $campoUsuario = in_array('user', $columnas, true) ? 'user' : 'usuario';
            $campoPassword = in_array('password', $columnas, true) ? 'password' : 'password_hash';

            $query = DB::table('usuario');
            if (! empty($username)) {
                $query->where($campoUsuario, $username);
            } elseif (! empty($nombre) && $nombreExiste) {
                $query->where('nombre', $nombre);
            }

            $user = $query->first();

            if ($user) {
                if (! empty($nombre) && $nombreExiste && ($user->nombre === 'Administrador' || empty($user->nombre))) {
                    DB::table('usuario')
                        ->where('id_usuario', $user->id_usuario)
                        ->update(['nombre' => $nombre]);
                }

                return (int) $user->id_usuario;
            }

            if (! empty($nombre) && $nombreExiste) {
                $userByNombre = DB::table('usuario')
                    ->where('nombre', $nombre)
                    ->first();
                if ($userByNombre) {
                    return (int) $userByNombre->id_usuario;
                }
            }

            $nombreFinal = ! empty($nombre) ? $nombre : ($username ?: 'Usuario');
            $userLogin = ! empty($username) ? $username : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombreFinal));
            if (empty($userLogin)) {
                $userLogin = 'usr_'.time();
            }

            $rolEnum = match (strtolower((string) $rol)) {
                'repartidor' => 'repartidor',
                'vendedor', 'preventista' => 'vendedor',
                'contador' => 'contador',
                'deposito' => 'deposito',
                default => 'administrativo',
            };

            $insertData = [
                $campoUsuario => $userLogin,
                'rol' => $rolEnum,
                'estado' => 'activo',
                $campoPassword => Hash::make('secret123'),
            ];

            if ($nombreExiste) {
                $insertData['nombre'] = $nombreFinal;
            }

            $newId = $this->crearUsuarioConClienteCompatible($insertData, $nombreFinal, $userLogin);

            return (int) $newId;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function crearUsuarioConClienteCompatible(array $insertData, string $nombreFinal, string $userLogin): int
    {
        if (Schema::hasTable('cliente')) {
            $clienteMax = (int) DB::table('cliente')->max('id_cliente');
            $usuarioMax = (int) DB::table('usuario')->max('id_usuario');
            $proximoId = max($clienteMax, $usuarioMax) + 1;
            $insertData['id_usuario'] = $proximoId;

            DB::table('cliente')->insertOrIgnore([
                'id_cliente' => $proximoId,
                'nombre' => $nombreFinal,
                'estado' => 'activo',
            ]);

            return (int) DB::table('usuario')->insertGetId($insertData);
        }

        return (int) DB::table('usuario')->insertGetId($insertData);
    }
}
