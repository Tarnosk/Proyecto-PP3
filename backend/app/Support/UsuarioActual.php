<?php
declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Resuelve la identidad del usuario que ejecuta la acción (auditoría).
 *
 * Division de responsabilidades:
 *  - G1 (autenticación): valida el login y deja disponible quién realiza
 *    la acción (atributo de request 'usuario_autenticado_id' y/o guard de
 *    Laravel que G1 configurará contra la tabla USUARIO).
 *  - G3 (auditoría): consume la identidad únicamente a traves de
 *    UsuarioActual::id(), jamas desde el body de la request (no confiable).
 *
 * Orden de resolución:
 *   1) Atributo de request 'usuario_autenticado_id' (inyectado por el
 *      middleware de identidad al detectar sesión, o por el middleware de G1).
 *   2) Guard 'proyecto' si G1 configuró ese nombre (contrato a convenir).
 *   3) Auth::id() del guard por defecto.
 *   4) Fallback: usuario administrativo activo mas bajo. Se resuelve
 *      contra la tabla USUARIO porque el id de la semilla no es fijo.
 */
final class UsuarioActual
{
    public const ATTRIBUTO = 'usuario_autenticado_id';

    private static ?int $fallback = null;

    public static function id(?Request $request = null): int
    {
        $request = $request ?? request();

        if ($request !== null) {
            $attr = $request->attributes->get(self::ATTRIBUTO);
            if (is_numeric($attr) && (int) $attr > 0) {
                return (int) $attr;
            }
        }

        $id = self::idDeGuard('proyecto');
        if ($id === null) {
            $id = Auth::id();
        }

        return ($id !== null && (int) $id > 0) ? (int) $id : self::idFallback();
    }

    /**
     * El id del usuario operativo no puede estar hardcodeado: las semillas
     * y los reimportes de esquema dejan el AUTO_INCREMENT desplazado y un
     * id fijo viola la FK de auditoria (producto.id_usuario_carga, etc.).
     */
    private static function idFallback(): int
    {
        if (self::$fallback !== null) {
            return self::$fallback;
        }

        try {
            $id = DB::table('usuario')
                ->where('estado', 'activo')
                ->orderByRaw("CASE WHEN rol = 'administrativo' THEN 0 ELSE 1 END")
                ->orderBy('id_usuario')
                ->value('id_usuario');

            self::$fallback = $id !== null ? (int) $id : 0;
        } catch (\Throwable $e) {
            self::$fallback = 0;
        }

        return self::$fallback;
    }

    /**
     * Lee el id del guard indicado solo si está configurado (evita lanzar
     * si G1 todavía no define su guard 'proyecto').
     */
    private static function idDeGuard(string $guard): ?int
    {
        if (config('auth.guards.' . $guard) === null) {
            return null;
        }

        $guardInstance = Auth::guard($guard);
        return $guardInstance->check() ? $guardInstance->id() : null;
    }
}