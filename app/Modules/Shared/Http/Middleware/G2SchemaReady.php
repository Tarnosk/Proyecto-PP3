<?php

namespace App\Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/** Sólo consulta metadatos: nunca instala el esquema ni modifica datos. */
class G2SchemaReady
{
    public const TABLES = [
        'caja' => ['CAJA', 'CAJA_MOVIMIENTO'],
        'conciliacion' => ['PERIODO_CONCILIACION', 'MOVIMIENTO_BANCARIO', 'CONCILIACION_DETALLE', 'AJUSTE_BANCARIO', 'CHEQUE', 'CAJA_MOVIMIENTO', 'RENDICION_COBRO'],
        'entregas' => ['entrega', 'entrega_pedido', 'remito', 'detalle_remito', 'RENDICION', 'RENDICION_COBRO'],
        'rendiciones' => ['RENDICION', 'RENDICION_COBRO', 'entrega', 'entrega_pedido', 'remito'],
    ];

    public function handle(Request $request, Closure $next, string $module)
    {
        $missing = array_values(array_filter(self::TABLES[$module], fn ($table) => ! Schema::hasTable($table)));
        if ($missing !== []) {
            Log::notice('G2: esquema pendiente de instalación', ['modulo' => $module, 'tablas' => $missing]);
            $message = 'Este apartado necesita que se complete la instalación de G2. Contactá al administrador.';

            return $request->expectsJson()
                ? response()->json(['error' => true, 'codigo' => 503, 'mensaje' => $message, 'data' => null], 503)
                : response()->view('modules.g2-unavailable', ['module' => $module, 'message' => $message], 503);
        }

        // El origen de cobros falta en la entrega de G2. No sustituirlo por otro
        // modelo financiero sin confirmar la correspondencia de sus claves.
        if ($module === 'conciliacion' && str_ends_with($request->route()->getActionName(), '@conciliarAutomatico')
            && ! class_exists(\App\Modules\ConciliacionBancaria\Repositories\Cobro::class)) {
            $message = 'La conciliación automática necesita que G2 complete la integración de cobros.';

            return $request->expectsJson()
                ? response()->json(['error' => true, 'codigo' => 503, 'mensaje' => $message], 503)
                : response()->view('modules.g2-unavailable', ['module' => $module, 'message' => $message], 503);
        }

        return $next($request);
    }
}
