<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Services\StockService;
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
            'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'id_usuario' => 'nullable|integer',
        ], [
            'id_producto.required' => 'Debe seleccionar el producto devuelto.',
            'cantidad.required' => 'Debe registrar la cantidad devuelta.',
            'cantidad.min' => 'La cantidad devuelta debe ser mayor a cero.',
            'motivo.required' => 'Debe indicar el motivo de la devolución.',
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

            $idUsuario = (int) $request->input('id_usuario', 1);
            $idUnidad = $request->has('id_unidad') ? (int) $request->input('id_unidad') : null;

            $this->stockService->registrarMovimiento(
                $producto,
                'devolucion',
                (int) $request->input('cantidad'),
                trim($request->input('motivo')),
                $idUsuario,
                $idUnidad
            );

            $producto->refresh();
            $stock = $producto->stock;

            return response()->json([
                'status' => 'success',
                'message' => 'Devolución registrada exitosamente.',
                'data' => [
                    'id_producto' => $producto->id_producto,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'cantidad_devuelta' => (int) $request->input('cantidad'),
                    'motivo' => trim($request->input('motivo')),
                    'stock_disponible' => (int) $stock->stock_disponible,
                    'estado_alerta' => $stock->estado_alerta,
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
