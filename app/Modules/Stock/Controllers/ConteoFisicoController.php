<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Stock\Models\DetalleConteo;
use App\Modules\Stock\Models\SesionConteo;
use App\Modules\Stock\Repositories\ConteoFisicoRepository;
use App\Modules\Stock\Requests\IniciarSesionConteoRequest;
use App\Modules\Stock\Requests\RegistrarItemConteoRequest;
use App\Modules\Stock\Requests\FinalizarSesionConteoRequest;
use App\Modules\Stock\Services\ConteoFisicoService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ConteoFisicoController extends Controller
{
    public function __construct(
        private ConteoFisicoService $service,
        private ConteoFisicoRepository $repository
    ) {
    }

    public function store(IniciarSesionConteoRequest $request): JsonResponse
    {
        $sesion = $this->service->iniciarSesion($request->validated(), UsuarioActual::id($request));

        return response()->json([
            'status' => 'success',
            'data' => $sesion,
            'message' => 'Sesión de conteo creada exitosamente.',
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $estado = $request->query('estado');
        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');

        $sesiones = $this->repository->listar($estado, $fechaDesde, $fechaHasta);

        return response()->json([
            'status' => 'success',
            'data' => $sesiones,
            'total' => $sesiones->count(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $sesion = $this->repository->obtenerSesion($id);
        if (!$sesion) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesión de conteo no encontrada.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'sesion' => $sesion,
                'resumen' => $this->service->obtenerResumen($id),
                'detalles' => $sesion->detalles()->with('producto')->get(),
            ],
        ]);
    }

    public function registrarItem(RegistrarItemConteoRequest $request, int $id): JsonResponse
    {
        try {
            $detalle = $this->service->registrarItem(
                $id,
                (int) $request->input('id_producto'),
                (int) $request->input('cantidad_fisica'),
                $request->input('observaciones')
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id_producto' => $detalle->id_producto,
                    'cantidad_fisica' => $detalle->cantidad_fisica,
                    'stock_sistema' => $detalle->stock_sistema,
                    'diferencia' => $detalle->diferencia,
                    'diagnostico' => $detalle->diagnostico,
                    'observaciones' => $detalle->observaciones,
                ],
                'message' => 'Ítem registrado en la sesión de conteo.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function eliminarItem(int $id, int $idProducto): JsonResponse
    {
        try {
            $this->service->eliminarItem($id, $idProducto);

            return response()->json([
                'status' => 'success',
                'message' => 'Ítem removido de la sesión.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function finalizar(FinalizarSesionConteoRequest $request, int $id): JsonResponse
    {
        $sesion = $this->repository->obtenerSesion($id);
        if (!$sesion) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesión de conteo no encontrada.',
            ], 404);
        }

        $sesionActualizada = $this->service->finalizarSesion(
            $id,
            (bool) $request->boolean('aplicar_ajustes', false),
            UsuarioActual::id($request)
        );

        $ajustesAplicados = Schema::hasColumn('MOVIMIENTO_STOCK', 'id_sesion_conteo')
            ? $sesionActualizada->movimientos()->count()
            : ($sesionActualizada->detalles()->where('diferencia', '!=', 0)->count() > 0 ? $sesionActualizada->detalles()->where('diferencia', '!=', 0)->count() : 0);

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión finalizada exitosamente.',
            'data' => $sesionActualizada,
            'ajustes_aplicados' => $ajustesAplicados,
        ]);
    }
}
