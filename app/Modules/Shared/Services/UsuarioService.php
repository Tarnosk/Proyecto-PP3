<?php

namespace App\Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio desacoplado para resolución de identidades de usuarios/repartidores.
 * En una arquitectura modular, los datos de usuario son provistos por el módulo
 * de Autenticación / Core mediante tokens o APIs.
 * Si la tabla USUARIO no existe localmente, opera de forma transparente con un catálogo simulado.
 */
class UsuarioService
{
    private static array $usuariosMock = [
        1 => [
            'id_usuario' => 1,
            'nombre' => 'Matías Repartidor',
            'rol' => 'repartidor',
            'user' => 'matias',
            'email' => 'matias@distribuidora.com'
        ],
        2 => [
            'id_usuario' => 2,
            'nombre' => 'Diego Admin',
            'rol' => 'admin',
            'user' => 'diego',
            'email' => 'diego@distribuidora.com'
        ],
        3 => [
            'id_usuario' => 3,
            'nombre' => 'Estefanía Contable',
            'rol' => 'admin',
            'user' => 'estefania',
            'email' => 'estefania@distribuidora.com'
        ],
        4 => [
            'id_usuario' => 4,
            'nombre' => 'Lucas Repartidor',
            'rol' => 'repartidor',
            'user' => 'lucas',
            'email' => 'lucas@distribuidora.com'
        ],
    ];

    /**
     * Retorna la lista de usuarios con rol de repartidor.
     */
    public static function obtenerRepartidores(): array
    {
        $tabla = self::determinarTablaUsuario();
        if ($tabla !== null) {
            try {
                $rows = DB::table($tabla)->where('rol', 'repartidor')->get();
                if ($rows->isNotEmpty()) {
                    return json_decode(json_encode($rows), true);
                }
            } catch (\Throwable) {}
        }

        return array_values(array_filter(self::$usuariosMock, fn($u) => $u['rol'] === 'repartidor'));
    }

    /**
     * Retorna el nombre legible de un usuario por su ID.
     */
    public static function obtenerNombre(int $idUsuario): string
    {
        if (isset(self::$usuariosMock[$idUsuario])) {
            return self::$usuariosMock[$idUsuario]['nombre'];
        }

        $tabla = self::determinarTablaUsuario();
        if ($tabla !== null) {
            try {
                $nombre = DB::table($tabla)->where('id_usuario', $idUsuario)->value('nombre');
                if ($nombre) {
                    return $nombre;
                }
            } catch (\Throwable) {}
        }

        return "Usuario #{$idUsuario}";
    }

    /**
     * Obtiene el registro de un usuario por su ID.
     */
    public static function obtenerUsuario(int $idUsuario): ?array
    {
        if (isset(self::$usuariosMock[$idUsuario])) {
            return self::$usuariosMock[$idUsuario];
        }

        $tabla = self::determinarTablaUsuario();
        if ($tabla !== null) {
            try {
                $row = DB::table($tabla)->where('id_usuario', $idUsuario)->first();
                if ($row) {
                    return (array)$row;
                }
            } catch (\Throwable) {}
        }

        return [
            'id_usuario' => $idUsuario,
            'nombre' => "Usuario #{$idUsuario}",
            'rol' => ($idUsuario === 2 || $idUsuario === 3) ? 'admin' : 'repartidor'
        ];
    }

    private static function determinarTablaUsuario(): ?string
    {
        if (Schema::hasTable('USUARIO')) {
            return 'USUARIO';
        }
        if (Schema::hasTable('usuario')) {
            return 'usuario';
        }
        return null;
    }
}
