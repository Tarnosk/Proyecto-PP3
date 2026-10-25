<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PedidosDeCompra\Models\SugerenciaCompra;
use App\Modules\PedidosDeCompra\Repositories\SugerenciaCompraRepository;
use App\Modules\PedidosDeCompra\Services\GeneradorSugerenciasReposicionService as Generador;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SugerenciasReposicionController extends Controller
{
    public function __construct(
        private Generador $service,
        private SugerenciaCompraRepository $repository
    ) {
    }

    public function generar(): JsonResponse
    {
        return $this->responder(fn () => $this->service->generarSugerencias(), 201, 500);
    }

    public function listar(Request $request): JsonResponse
    {
        return $this->responder(function () use ($request) {
            $r = $this->repository->obtenerConFiltros(
                $request->only(['motivo', 'estado']),
                max(1, (int) $request->input('pagina', 1))
            );

            return [
                'exito' => true,
                'datos' => collect($r['data'])->map(fn (SugerenciaCompra $s) => Generador::formatear($s))->all(),
                'paginacion' => $r['paginacion'],
            ];
        }, 200, 500);
    }

    public function procesar(Request $request, int $id): JsonResponse
    {
        return $this->responder(fn () => $this->service->procesarSugerencia($id, UsuarioActual::id($request)), 200, 400);
    }

    public function rechazar(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        return $this->responder(
            fn () => $this->service->rechazarSugerencia($id, UsuarioActual::id($request), $validated['motivo'] ?? null),
            200,
            400
        );
    }

    public function procesarLote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        return $this->responder(
            fn () => $this->service->procesarMultiples($validated['ids'], UsuarioActual::id($request)),
            200,
            400
        );
    }

    /**
     * PC07 - Generar una orden de compra a partir de la sugerencia, asociada al
     * proveedor que el generador le asignó.
     */
    public function generarOrden(Request $request, int $id): JsonResponse
    {
        return $this->responder(
            fn () => $this->service->generarOrdenDesdeSugerencia($id, UsuarioActual::id($request)),
            201,
            400
        );
    }

    public function resumen(): JsonResponse
    {
        return $this->responder(fn () => $this->service->obtenerResumen(), 200, 500);
    }

    private function responder(callable $accion, int $ok, int $codigoError): JsonResponse
    {
        try {
            return response()->json($accion(), $ok);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json(['exito' => false, 'error' => $e->getMessage()], $codigoError);
        }
    }
}
