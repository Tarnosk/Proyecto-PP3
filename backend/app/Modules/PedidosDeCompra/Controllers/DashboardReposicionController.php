<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PedidosDeCompra\Services\DashboardReposicionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DashboardReposicionController extends Controller
{
    public function __construct(private DashboardReposicionService $service)
    {
    }

    public function resumen(): JsonResponse
    {
        return $this->responder(fn () => $this->service->obtenerResumenGeneral());
    }

    public function graficoMotivos(): JsonResponse
    {
        return $this->responder(fn () => $this->service->obtenerDatosGraficoMotivos());
    }

    public function graficoTendencia(Request $request): JsonResponse
    {
        $dias = min(365, max(1, (int) $request->input('dias', 30)));

        return $this->responder(fn () => $this->service->obtenerDatosGraficoTendencia($dias));
    }

    public function graficoCriticidad(): JsonResponse
    {
        return $this->responder(fn () => $this->service->obtenerDatosGraficoCriticidad());
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
