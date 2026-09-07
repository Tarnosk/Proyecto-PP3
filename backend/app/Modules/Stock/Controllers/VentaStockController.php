<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VentaStockController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S04 - Actualización automática de stock por venta.
     *
     * Endpoint interno simple (el Grupo 4 lo invocará al facturar una venta).
     * Recibe id_producto y cantidad vendida; descuenta el stock, impide que
     * resulte negativo y registra un movimiento tipo 'venta'.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'cantidad' => 'required|integer|min:1',
            'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'id_venta' => 'nullable|integer',
            'id_usuario' => 'nullable|integer',
            'motivo' => 'nullable|string|max:255',
        ], [
            'id_producto.required' => 'Debe indicar el producto vendido.',
            'cantidad.required' => 'Debe indicar la cantidad vendida.',
            'cantidad.min' => 'La cantidad vendida debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $producto = Producto::find((int) $request->input('id_producto'));

            if (!$producto || $producto->estado !== 'activo') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El producto no está activo o no existe.',
                ], 400);
            }

            $idUsuario = (int) $request->input('id_usuario', 1);
            $idUnidad = $request->has('id_unidad') ? (int) $request->input('id_unidad') : null;
            $idVenta = $request->has('id_venta') ? (int) $request->input('id_venta') : null;
            $motivo = $request->input('motivo') ?? 'Venta registrada (S04)';

            $this->stockService->registrarMovimiento(
                $producto,
                'venta',
                (int) $request->input('cantidad'),
                $motivo,
                $idUsuario,
                $idUnidad,
                $idVenta
            );

            $producto->refresh();
            $stock = $producto->stock;

            return response()->json([
                'status' => 'success',
                'message' => 'Stock actualizado por la venta. Se descontó ' . $request->input('cantidad') . ' unidad(es).',
                'data' => [
                    'id_producto' => $producto->id_producto,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'cantidad_vendida' => (int) $request->input('cantidad'),
                    'stock_disponible' => (int) $stock->stock_disponible,
                    'estado_alerta' => $stock->estado_alerta,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
