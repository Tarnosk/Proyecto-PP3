<?php

namespace App\Modules\ConciliacionBancaria\Controllers;

use App\Modules\ConciliacionBancaria\Services\ConciliacionService;
use App\Modules\Shared\Http\Controllers\Controller;
use App\Modules\Shared\Http\Response;
use Illuminate\Http\Request;

/**
 * Controller único para el módulo de Conciliación Bancaria.
 * Toda la lógica de negocio vive en ConciliacionService — este Controller
 * solo valida el request y traduce el resultado del Service a HTTP.
 */
class ConciliacionController extends Controller
{
    public function __construct(private ConciliacionService $service)
    {
    }

    /**
     * GET /conciliacion
     */
    public function index(Request $request)
    {
        $estado = $request->query('estado');

        Response::json($this->service->listarPeriodos($estado));
    }

    /**
     * GET /conciliacion/{id}
     */
    public function show(int $id)
    {
        $periodo = $this->service->buscarPeriodo($id);

        if ($periodo === null) {
            Response::error('Período de conciliación no encontrado', 404);
            return;
        }

        Response::json($periodo);
    }

    /**
     * POST /conciliacion
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
            'id_usuario' => ['required', 'integer'],
        ]);

        $periodo = $this->service->crearPeriodo($datos);

        Response::json($periodo, 201);
    }

    /**
     * PATCH /conciliacion/{id}/conciliar
     */
    public function conciliarAutomatico(int $id)
    {
        $resultado = $this->service->conciliarAutomatico($id);

        if ($resultado === null) {
            Response::error('Período de conciliación no encontrado', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 400);
            return;
        }

        Response::json($resultado);
    }

    /**
     * POST /conciliacion/movimientos/{id}/conciliar-manual
     * PROPUESTO — no confirmado aún con el equipo.
     */
    public function conciliarManual(Request $request, int $id)
    {
        $datos = $request->validate([
            'id_cobro' => ['nullable', 'integer'],
            'id_movimiento_caja' => ['nullable', 'integer'],
            'id_ajuste' => ['nullable', 'integer'],
            'id_cheque' => ['nullable', 'integer'],
            'id_usuario' => ['required', 'integer'],
        ]);

        $resultado = $this->service->conciliarManual($id, $datos);

        if ($resultado === null) {
            Response::error('Movimiento bancario no encontrado', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 400);
            return;
        }

        Response::json($resultado, 201);
    }

    /**
     * PATCH /conciliacion/{id}/cerrar
     */
    public function cerrar(int $id)
    {
        $resultado = $this->service->cerrarPeriodo($id);

        if ($resultado === null) {
            Response::error('Período de conciliación no encontrado', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 400);
            return;
        }

        Response::json($resultado);
    }
}