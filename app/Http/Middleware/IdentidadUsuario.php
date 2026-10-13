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
        // 1. Identidad enviada desde el frontend SPA / cliente HTTP
        $headerUsername = $request->header('X-User-Username');
        $headerNameRaw = $request->header('X-User-Name');
        $headerName = $headerNameRaw ? urldecode($headerNameRaw) : null;
        $headerRole = $request->header('X-User-Role');

        if (!empty($headerUsername) || !empty($headerName)) {
            $resolvedId = $this->resolverOCrearUsuario($headerUsername, $headerName, $headerRole);
            if ($resolvedId !== null) {
                return $resolvedId;
            }
        }

        // 2. Guard de Laravel configurado
        $guard = config('auth.guards.proyecto') !== null ? \Illuminate\Support\Facades\Auth::guard('proyecto') : null;

        if ($guard !== null && $guard->check()) {
            return (int) $guard->id();
        }

        if (\Illuminate\Support\Facades\Auth::check()) {
            return (int) \Illuminate\Support\Facades\Auth::id();
        }

        return null;
    }

    private function resolverOCrearUsuario(?string $username, ?string $nombre, ?string $rol): ?int
    {
        try {
            $columnas = \Illuminate\Support\Facades\Schema::getConnection()->getSchemaBuilder()->getColumnListing('usuario');
            $nombreExiste = in_array('nombre', $columnas, true);
            $campoUsuario = in_array('user', $columnas, true) ? 'user' : 'usuario';
            $campoPassword = in_array('password', $columnas, true) ? 'password' : 'password_hash';

            $query = \Illuminate\Support\Facades\DB::table('usuario');
            if (!empty($username)) {
                $query->where($campoUsuario, $username);
            } elseif (!empty($nombre) && $nombreExiste) {
                $query->where('nombre', $nombre);
            }

            $user = $query->first();

            if ($user) {
                if (!empty($nombre) && $nombreExiste && ($user->nombre === 'Administrador' || empty($user->nombre))) {
                    \Illuminate\Support\Facades\DB::table('usuario')
                        ->where('id_usuario', $user->id_usuario)
                        ->update(['nombre' => $nombre]);
                }
                return (int) $user->id_usuario;
            }

            if (!empty($nombre) && $nombreExiste) {
                $userByNombre = \Illuminate\Support\Facades\DB::table('usuario')
                    ->where('nombre', $nombre)
                    ->first();
                if ($userByNombre) {
                    return (int) $userByNombre->id_usuario;
                }
            }

            $nombreFinal = !empty($nombre) ? $nombre : ($username ?: 'Usuario');
            $userLogin = !empty($username) ? $username : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombreFinal));
            if (empty($userLogin)) {
                $userLogin = 'usr_' . time();
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
                $campoPassword => \Illuminate\Support\Facades\Hash::make('secret123'),
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
        if (\Illuminate\Support\Facades\Schema::hasTable('cliente')) {
            $clienteMax = (int) \Illuminate\Support\Facades\DB::table('cliente')->max('id_cliente');
            $usuarioMax = (int) \Illuminate\Support\Facades\DB::table('usuario')->max('id_usuario');
            $proximoId = max($clienteMax, $usuarioMax) + 1;
            $insertData['id_usuario'] = $proximoId;

            \Illuminate\Support\Facades\DB::table('cliente')->insertOrIgnore([
                'id_cliente' => $proximoId,
                'nombre' => $nombreFinal,
                'estado' => 'activo',
            ]);

            return (int) \Illuminate\Support\Facades\DB::table('usuario')->insertGetId($insertData);
        }

        return (int) \Illuminate\Support\Facades\DB::table('usuario')->insertGetId($insertData);
    }
}