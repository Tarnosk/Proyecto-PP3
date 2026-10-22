<?php

namespace App\Modules\Ventas\Controllers;

use App\Modules\Ventas\Contracts\ProductoGateway;
use App\Modules\Ventas\Enums\EstadoVenta;
use App\Modules\Ventas\Exceptions\VentaException;
use App\Http\Controllers\Controller;
use App\Modules\Ventas\Requests\AnularVentaRequest;
use App\Modules\Ventas\Requests\CambiarEstadoVentaRequest;
use App\Modules\Ventas\Requests\ListarVentasRequest;
use App\Modules\Ventas\Requests\ModificarVentaRequest;
use App\Modules\Ventas\Resources\VentaResource;
use App\Modules\Ventas\Logging\VentaLogger;
use App\Modules\Ventas\Models\Venta;
use App\Modules\Ventas\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * API DEL MÓDULO VENTAS (rutas en app/Modules/Ventas/routes/api.php, todas empiezan con /api/ventas)
 *
 *   GET    /api/ventas                   → index()         lista con filtros y paginación
 *   GET    /api/ventas/estados           → estados()       catálogo de estados y sus reglas
 *   GET    /api/ventas/productos         → productos()     catálogo para "Agregar producto" al modificar
 *   GET    /api/ventas/{id}              → show()          una venta con detalle, botones e historial
 *   PUT    /api/ventas/{id}              → update()        botón "Modificar"
 *   DELETE /api/ventas/{id}              → destroy()       botón "Eliminar" (ANULA, no borra)
 *   POST   /api/ventas/{id}/restaurar    → restaurar()     deshace una anulación
 *   PATCH  /api/ventas/{id}/estado       → cambiarEstado() avanza de fase (preparada, entregada, pagada...)
 *
 * El controlador es "fino": recibe el pedido, llama a VentaService (donde
 * están las reglas) y devuelve la respuesta. Las operaciones que cambian
 * datos dejan su informe paso a paso en storage/logs/ventas/.
 *
 * Respuestas posibles:
 *   200 OK · 404 no existe · 409 el estado no lo permite · 422 datos inválidos
 *   500 error inesperado (no se guarda nada) · 503 otro módulo no responde
 */
class VentaApiController extends Controller
{
    public function __construct(private readonly VentaService $ventas) {}

    /**
     * GET /api/ventas?estado=&cliente_id=&desde=&hasta=&texto=&por_pagina=&page=
     */
    public function index(ListarVentasRequest $request): AnonymousResourceCollection
    {
        return VentaResource::collection($this->ventas->listar($request->validated()));
    }

    /**
     * GET /api/ventas/estados → los 7 estados con sus reglas (para armar botones y combos).
     */
    public function estados(): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn (EstadoVenta $estado) => $estado->aArray(), EstadoVenta::cases()),
        ]);
    }

    /**
     * GET /api/ventas/productos → catálogo de productos para el editor de "Modificar"
     * (el selector de "Agregar producto").
     *
     * Sale de ProductoGateway: hoy el mock (MockProductoApi), mañana la API real
     * de Productos, sin cambiar nada en el front.
     */
    public function productos(ProductoGateway $productos): JsonResponse
    {
        $catalogo = array_map(fn (array $p) => [
            'id' => (int) $p['id'],
            'codigo' => $p['codigo'] ?? null,
            'nombre' => $p['nombre'],
            'precioMin' => (float) $p['precioMin'],
            'precioMay' => (float) $p['precioMay'],
            'stock' => isset($p['stock']) ? (int) $p['stock'] : null,
        ], $productos->todos());

        return response()->json(['data' => array_values($catalogo)]);
    }

    /**
     * GET /api/ventas/{id}
     */
    public function show(int $id): VentaResource
    {
        return new VentaResource($this->ventas->buscar($id));
    }

    /**
     * PUT /api/ventas/{id} → botón "Modificar".
     */
    public function update(ModificarVentaRequest $request, int $id): JsonResponse
    {
        $log = new VentaLogger($request, 'MODIFICAR VENTA', 'VentaApiController@update', 7);

        return $this->ejecutar($log, 'VENTA MODIFICADA', 'Venta modificada correctamente.',
            fn () => $this->ventas->modificar($id, $request->validated(), $log));
    }

    /**
     * DELETE /api/ventas/{id} → botón "Eliminar". Anula la venta (no la borra).
     */
    public function destroy(AnularVentaRequest $request, int $id): JsonResponse
    {
        $log = new VentaLogger($request, 'ANULAR VENTA', 'VentaApiController@destroy', 6);

        return $this->ejecutar($log, 'VENTA ANULADA', 'Venta anulada. Se puede restaurar desde el historial.',
            fn () => $this->ventas->anular($id, $request->validated('motivo'), $log));
    }

    /**
     * POST /api/ventas/{id}/restaurar → deshace una anulación.
     */
    public function restaurar(Request $request, int $id): JsonResponse
    {
        $log = new VentaLogger($request, 'RESTAURAR VENTA', 'VentaApiController@restaurar', 6);

        return $this->ejecutar($log, 'VENTA RESTAURADA', 'Venta restaurada.',
            fn () => $this->ventas->restaurar($id, $log));
    }

    /**
     * PATCH /api/ventas/{id}/estado → avanza de fase.
     */
    public function cambiarEstado(CambiarEstadoVentaRequest $request, int $id): JsonResponse
    {
        $log = new VentaLogger($request, 'CAMBIAR ESTADO DE VENTA', 'VentaApiController@cambiarEstado', 4);
        $destino = EstadoVenta::from($request->validated('estado'));

        return $this->ejecutar($log, 'ESTADO CAMBIADO', "La venta pasó a \"{$destino->value}\".",
            fn () => $this->ventas->cambiarEstado($id, $destino, $request->validated('motivo'), $log));
    }

    /**
     * Ejecuta una operación que CAMBIA una venta y arma la respuesta.
     * Es el mismo esquema para las 4 operaciones:
     *
     *   - salió bien            → 200 { message, data: venta }       (log: INFO)
     *   - datos inválidos       → 422 (lo arma Laravel)               (log: WARNING)
     *   - regla de negocio      → 404 / 409 / 503 (VentaException)    (log: WARNING)
     *   - cualquier otro error  → 500 { message, detalle }            (log: ERROR)
     *     "detalle" solo se muestra con APP_DEBUG=true (en producción no
     *     hay que mostrarle al usuario errores internos).
     *
     * @param  callable(): Venta  $operacion
     */
    private function ejecutar(VentaLogger $log, string $resultado, string $mensaje, callable $operacion): JsonResponse
    {
        try {
            $venta = $operacion();

            $respuesta = [
                'message' => $mensaje,
                'data' => (new VentaResource($venta))->resolve(),
            ];
            $log->exito($respuesta, 200, $resultado);

            return response()->json($respuesta, 200);
        } catch (ValidationException $e) {
            $log->rechazada($e);
            throw $e; // Laravel responde 422 con { message, errors }
        } catch (VentaException $e) {
            $log->bloqueada($e);
            throw $e; // la excepción arma su propia respuesta (método render)
        } catch (Throwable $e) {
            $respuesta = [
                'message' => 'Hubo un error al procesar la venta. No se guardó ningún cambio.',
                'detalle' => config('app.debug') ? $e->getMessage() : null,
            ];
            $log->error($e, $respuesta);

            return response()->json($respuesta, 500);
        }
    }
}
