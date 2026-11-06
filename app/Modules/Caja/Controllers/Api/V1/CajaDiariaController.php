<?php

namespace App\Modules\Caja\Controllers\Api\V1;

use App\Modules\Caja\Services\CajaService;
use App\Modules\Shared\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * GET /api/v1/caja/{fecha} — caja diaria para Reportes (G1).
 *
 * Solo lectura. El acceso lo resuelve el middleware de la ruta (token interno
 * de servicio, o usuario administrativo/contador): este controller no depende
 * de la sesión web ni del actor.
 */
class CajaDiariaController extends Controller
{
    public function __construct(private CajaService $service)
    {
    }

    public function __invoke(string $fecha): JsonResponse
    {
        // La ruta ya exige el formato AAAA-MM-DD; esto rechaza fechas imposibles (2026-13-45).
        Validator::validate(['fecha' => $fecha], ['fecha' => ['required', 'date_format:Y-m-d']]);

        return response()
            ->json($this->service->obtenerCajaDiaria($fecha))
            ->header('Cache-Control', 'no-store');
    }
}
