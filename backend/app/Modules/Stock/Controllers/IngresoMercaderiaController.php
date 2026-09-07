<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Stock\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IngresoMercaderiaController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S03 - Registrar ingreso de mercadería de un proveedor.
     * Recibe una lista de productos con cantidades ingresadas y actualiza el stock.
     * Se valida opcionalmente la existencia del proveedor (PV01).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_proveedor' => 'required|integer|exists:PROVEEDOR,id_proveedor',
            'fecha' => 'nullable|date|before_or_equal:today',
            'id_usuario' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'items.*.motivo' => 'nullable|string|max:255',
        ], [
            'id_proveedor.required' => 'Debe seleccionar un proveedor.',
            'id_proveedor.exists' => 'El proveedor seleccionado no existe.',
            'fecha.before_or_equal' => 'La fecha del ingreso no puede ser posterior a la fecha actual.',
            'items.required' => 'Debe indicar al menos un producto recibido.',
            'items.*.id_producto.required' => 'Cada ítem debe indicar un producto.',
            'items.*.id_producto.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.cantidad.required' => 'Cada ítem debe indicar la cantidad ingresada.',
            'items.*.cantidad.min' => 'La cantidad ingresada debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        // PV04: impedir nuevas operaciones con proveedores inactivos.
        $proveedor = Proveedor::find((int) $request->input('id_proveedor'));
        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => 'El proveedor seleccionado no existe.',
            ], 404);
        }
        if ($proveedor->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede registrar un ingreso con un proveedor inactivo. (PV04)',
            ], 400);
        }

        $idUsuario = (int) $request->input('id_usuario', 1);
        $fecha = $request->input('fecha');

        try {
            $registrados = [];
            $items = $request->input('items');

            foreach ($items as $item) {
                $producto = Producto::find((int) $item['id_producto']);
                $idUnidad = isset($item['id_unidad']) ? (int) $item['id_unidad'] : null;
                $motivo = $item['motivo'] ?? 'Ingreso de mercadería (S03)';
                $cantidad = (int) $item['cantidad'];

                if (!$producto || $producto->estado !== 'activo') {
                    return response()->json([
                        'status' => 'error',
                        'message' => "El producto con ID {$item['id_producto']} no está activo o no existe.",
                    ], 400);
                }

                $this->stockService->registrarMovimiento(
                    $producto,
                    'ingreso',
                    $cantidad,
                    $motivo,
                    $idUsuario,
                    $idUnidad
                );

                $producto->refresh();
                $stock = $producto->stock;

                $registrados[] = [
                    'id_producto' => $producto->id_producto,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'cantidad' => $cantidad,
                    'id_unidad' => $idUnidad,
                    'stock_disponible' => (int) $stock->stock_disponible,
                    'estado_alerta' => $stock->estado_alerta,
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Ingreso de mercadería registrado exitosamente.',
                'data' => [
                    'id_proveedor' => (int) $request->input('id_proveedor'),
                    'fecha' => $fecha,
                    'items' => $registrados,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el ingreso de mercadería: ' . $e->getMessage(),
            ], 500);
        }
    }
}
