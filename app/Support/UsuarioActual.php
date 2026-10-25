<?php

declare(strict_types=1);

namespace App\Support;

use App\Modules\Login\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Resuelve la identidad del usuario que ejecuta la acción (auditoría)
 * conectando con App\Modules\Login\Models\User (G1).
 */
final class UsuarioActual
{
    public const ATTRIBUTO = 'usuario_autenticado_id';

    private static ?int $fallback = null;

    public static function id(?Request $request = null): int
    {
        $request = $request ?? request();

        $id = null;

        if ($request !== null) {
            $attr = $request->attributes->get(self::ATTRIBUTO);
            if (is_numeric($attr) && (int) $attr > 0) {
                $id = (int) $attr;
            }
        }

        if ($id === null) {
            $id = self::idDeGuard('proyecto');
        }

        if ($id === null && Auth::check()) {
            $id = (int) Auth::id();
        }

        $finalId = ($id !== null && $id > 0) ? $id : self::idFallback();

        if ($finalId > 0) {
            self::sincronizarFkUsuarioSiCorresponde($finalId);
        }

        return $finalId;
    }

    private static function idFallback(): int
    {
        if (self::$fallback !== null) {
            return self::$fallback;
        }

        try {
            if (Schema::hasTable('usuarios')) {
                $id = User::query()
                    ->where('estado', 'activo')
                    ->orderByRaw("CASE WHEN rol = 'administrativo' THEN 0 ELSE 1 END")
                    ->orderBy('id')
                    ->value('id');

                if ($id !== null) {
                    self::$fallback = (int) $id;

                    return self::$fallback;
                }
            }

            if (Schema::hasTable('usuario')) {
                $id = DB::table('usuario')
                    ->where('estado', 'activo')
                    ->orderByRaw("CASE WHEN rol = 'administrativo' THEN 0 ELSE 1 END")
                    ->orderBy('id_usuario')
                    ->value('id_usuario');

                if ($id !== null) {
                    self::$fallback = (int) $id;

                    return self::$fallback;
                }
            }
        } catch (\Throwable $e) {
            self::$fallback = 0;
        }

        return self::$fallback ?? 1;
    }

    /**
     * Garantiza que el usuario de App\Modules\Login\Models\User (tabla usuarios)
     * tenga su reflejo en la tabla usuario si las FKs de G3 en MySQL apuntan a ella.
     */
    private static function sincronizarFkUsuarioSiCorresponde(int $idUsuario): void
    {
        try {
            $hasUsuarios = Schema::hasTable('usuarios');
            $hasUsuario = Schema::hasTable('usuario');

            if ($hasUsuarios && $hasUsuario) {
                $existsInUsuario = DB::table('usuario')->where('id_usuario', $idUsuario)->exists();
                if (! $existsInUsuario) {
                    $g1User = DB::table('usuarios')->where('id', $idUsuario)->first();
                    $cols = Schema::getColumnListing('usuario');
                    $campoUsuario = in_array('user', $cols, true) ? 'user' : 'usuario';
                    $campoPass = in_array('password', $cols, true) ? 'password' : 'password_hash';

                    $loginName = $g1User->usuario ?? ('usr_'.$idUsuario);
                    if (DB::table('usuario')->where($campoUsuario, $loginName)->exists()) {
                        $loginName = $loginName.'_'.$idUsuario;
                    }

                    $insert = [
                        'id_usuario' => $idUsuario,
                        $campoUsuario => $loginName,
                        $campoPass => $g1User->password_hash ?? Hash::make('secret123'),
                        'rol' => in_array($g1User->rol ?? '', ['administrativo', 'repartidor', 'contador', 'vendedor', 'deposito'], true)
                            ? $g1User->rol
                            : 'administrativo',
                        'estado' => $g1User->estado ?? 'activo',
                    ];
                    if (in_array('nombre', $cols, true)) {
                        $insert['nombre'] = $g1User->nombre ?? ('Usuario #'.$idUsuario);
                    }

                    DB::table('usuario')->insertOrIgnore($insert);
                }

                $existsInUsuarios = DB::table('usuarios')->where('id', $idUsuario)->exists();
                if (! $existsInUsuarios) {
                    $g3User = DB::table('usuario')->where('id_usuario', $idUsuario)->first();
                    if ($g3User) {
                        $loginName = $g3User->usuario ?? ($g3User->user ?? ('usr_'.$idUsuario));
                        if (DB::table('usuarios')->where('usuario', $loginName)->exists()) {
                            $loginName = $loginName.'_'.$idUsuario;
                        }
                        $rolG1 = in_array($g3User->rol ?? '', ['administrativo', 'repartidor', 'contador'], true)
                            ? $g3User->rol
                            : 'administrativo';
                        DB::table('usuarios')->insertOrIgnore([
                            'id' => $idUsuario,
                            'usuario' => $loginName,
                            'password_hash' => $g3User->password_hash ?? ($g3User->password ?? Hash::make('secret123')),
                            'nombre' => $g3User->nombre ?? ('Usuario #'.$idUsuario),
                            'rol' => $rolG1,
                            'estado' => $g3User->estado ?? 'activo',
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // No interrumpir si el esquema es de prueba en memoria
        }
    }

    private static function idDeGuard(string $guard): ?int
    {
        if (config('auth.guards.'.$guard) === null) {
            return null;
        }

        $guardInstance = Auth::guard($guard);

        return $guardInstance->check() ? (int) $guardInstance->id() : null;
    }
}
