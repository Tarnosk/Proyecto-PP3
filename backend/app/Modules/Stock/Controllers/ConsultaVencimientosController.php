<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Services\ConsultaVencimientosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * S11 - Consulta de vencimientos. Sin middleware propio: la identidad la aporta
 * IdentidadUsuario (grupo 'api'); cuando G1 integre el login, el middleware de
 * autenticación se agrega sobre el grupo de rutas, no en cada constructor.
 */
class ConsultaVencimientosController extends Controller
{
    public function __construct(private ConsultaVencimientosService $service)
    {
    }

    public function proximosAVencer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dias' => 'integer|min:1|max:365',
            'pagina' => 'integer|min:1',
            'producto' => 'nullable|string|max:255',
            // S11: "Debe permitir ordenar por fecha de vencimiento".
            'orden' => 'nullable|string|in:fecha_vencimiento,cantidad,producto,dias_restantes',
            'direccion' => 'nullable|string|in:asc,desc',
        ]);

        return $this->responder(fn () => $this->service->obtenerProximosVencer(
            (int) ($validated['dias'] ?? 30),
            array_filter(['producto' => $validated['producto'] ?? null]),
            (int) ($validated['pagina'] ?? 1),
            $validated['orden'] ?? 'fecha_vencimiento',
            $validated['direccion'] ?? 'asc'
        ));
    }

    public function porCriticidad(): JsonResponse
    {
        return $this->responder(fn () => $this->service->obtenerPorCriticidad());
    }

    public function alertas(): JsonResponse
    {
        return $this->responder(fn () => $this->service->obtenerAlertas());
    }

    public function reporte(): JsonResponse
    {
        return $this->responder(fn () => $this->service->generarReporte());
    }

    private function responder(callable $accion): JsonResponse
    {
        try {
            return response()->json($accion(), 200);
        } catch (Throwable $e) {
            return response()->json(['exito' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
