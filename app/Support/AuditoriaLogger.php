<?php
declare(strict_types=1);

namespace App\Support;

use App\Models\LogAuditoria;
use Carbon\Carbon;
use Throwable;

final class AuditoriaLogger
{
    public static function registrar(
        string $tabla,
        int $idRegistro,
        string $accion,
        ?array $anteriores,
        ?array $nuevos,
        int $idUsuario
    ): void {
        try {
            LogAuditoria::create([
                'tabla_afectada' => $tabla,
                'id_registro' => $idRegistro,
                'accion' => in_array($accion, ['INSERT', 'UPDATE', 'DELETE'], true) ? $accion : 'UPDATE',
                'valores_anteriores' => $anteriores,
                'valores_nuevos' => $nuevos,
                'id_usuario' => $idUsuario > 0 ? $idUsuario : null,
                'fecha_hora' => Carbon::now(),
            ]);
        } catch (Throwable $e) {
            logger()->warning('Fallo al registrar log_auditoria: ' . $e->getMessage());
        }
    }
}
