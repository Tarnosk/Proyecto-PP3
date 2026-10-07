<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Services\StockService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DevolucionController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S05 - Registrar devolución de un producto al depósito.
     * Selecciona el producto devuelto, su cantidad y motivo; repone el stock
     * y registra un movimiento tipo 'devolucion'.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
            'tipo_devolucion' => 'nullable|string|in:cliente,proveedor',
            'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'fecha' => 'nullable|date|before_or_equal:today',
        ], [
            'id_producto.required' => 'Debe seleccionar el producto devuelto.',
            'cantidad.required' => 'Debe registrar la cantidad devuelta.',
            'cantidad.min' => 'La cantidad devuelta debe ser mayor a cero.',
            'motivo.required' => 'Debe indicar el motivo de la devolución.',
            'tipo_devolucion.in' => 'El tipo de devolución debe ser cliente o proveedor.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $producto = Producto::find((int) $request->input('id_producto'));

            if (!$producto) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Producto no encontrado.',
                ], 404);
            }

            if ($producto->estado !== 'activo') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se pueden registrar devoluciones sobre productos inactivos.',
                ], 400);
            }

            $idUsuario = UsuarioActual::id($request);
            $idUnidad = $request->has('id_unidad') ? (int) $request->input('id_unidad') : null;
            $cantidad = (int) $request->input('cantidad');

            $tipoDevolucion = $request->input('tipo_devolucion', 'cliente');
            // Cliente: devuelve mercadería previamente vendida -> SUMA al stock ($esIncremento = true)
            // Proveedor: se devuelve mercadería al proveedor -> DESCUENTA del stock ($esIncremento = false)
            $esIncremento = ($tipoDevolucion === 'cliente');

            $cantidadBase = StockService::cantidadEnUnidadBase($cantidad, $idUnidad);
            if (!$esIncremento && $producto->stock_disponible < $cantidadBase) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Stock insuficiente para devolver al proveedor. Stock actual disponible: {$producto->stock_disponible}, solicitado: {$cantidadBase}.",
                ], 400);
            }

            $fechaDev = null;
            if ($request->has('fecha') && $request->input('fecha')) {
                try {
                    $fechaDev = \Carbon\Carbon::parse($request->input('fecha'));
                    if (!str_contains((string) $request->input('fecha'), ':')) {
                        $ahora = \Carbon\Carbon::now();
                        $fechaDev->setTime($ahora->hour, $ahora->minute, $ahora->second);
                    }
                } catch (\Throwable $e) {
                    $fechaDev = \Carbon\Carbon::now();
                }
            } else {
                $fechaDev = \Carbon\Carbon::now();
            }

            $prefijo = $esIncremento ? '[Devolución Cliente]' : '[Devolución a Proveedor]';
            $motivoRaw = trim((string) $request->input('motivo'));
            $motivoFinal = str_starts_with($motivoRaw, '[Devolución') ? $motivoRaw : "{$prefijo} {$motivoRaw}";

            $this->stockService->registrarMovimiento(
                $producto,
                'devolucion',
                $cantidad,
                $motivoFinal,
                $idUsuario,
                $idUnidad,
                null,
                null,
                $fechaDev,
                $esIncremento
            );

            $producto->refresh();

            $mensaje = $esIncremento
                ? "Devolución de cliente registrada exitosamente (+{$cantidad} u. sumadas al stock)."
                : "Devolución a proveedor registrada exitosamente (-{$cantidad} u. descontadas del stock).";

            return response()->json([
                'status' => 'success',
                'message' => $mensaje,
                'data' => [
                    'id_producto' => $producto->id_producto,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'tipo_devolucion' => $tipoDevolucion,
                    'impacto_stock' => $esIncremento ? "+{$cantidad}" : "-{$cantidad}",
                    'cantidad_devuelta' => $cantidad,
                    'motivo' => $motivoFinal,
                    'stock_disponible' => $producto->stock_disponible,
                    'estado_alerta' => $producto->estado_alerta,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
