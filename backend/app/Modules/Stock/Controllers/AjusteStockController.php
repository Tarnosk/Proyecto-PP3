<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AjusteStockController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S06 - Ajuste manual de inventario (rotura, pérdida, correcciones).
     * Permite aumentar o disminuir el stock. Requiere un motivo obligatorio.
     *
     * @param int $tipo 1 = incremento, -1 = decremento (o usar 'aumentar'/'disminuir')
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'cantidad' => 'required|numeric|gt:0',
            'tipo' => 'required|in:aumentar,disminuir',
            'motivo' => 'required|string|max:255',
            'id_usuario' => 'nullable|integer',
        ], [
            'id_producto.required' => 'Debe seleccionar un producto.',
            'cantidad.required' => 'Debe registrar la cantidad ajustada.',
            'cantidad.gt' => 'La cantidad ajustada debe ser mayor a cero.',
            'tipo.required' => 'Debe indicar si el ajuste es para aumentar o disminuir.',
            'tipo.in' => 'El tipo de ajuste debe ser "aumentar" o "disminuir".',
            'motivo.required' => 'Debe indicar el motivo del ajuste (rotura, pérdida, corrección...).',
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
            $cantidad = (int) $request->input('cantidad');
            $tipo = $request->input('tipo');

            // 'ajuste' en el servicio siempre decrementa; para incrementar usamos
            // un movimiento de ajuste positivo = ingreso por ajuste. Para evitar
            // confusión, reutilizamos 'ingreso' para incrementos y 'ajuste' para
            // decrementos, pero todos quedan registrados como ajuste manual en el motivo.
            $tipoMovimiento = $tipo === 'disminuir' ? 'ajuste' : 'ingreso';
            $motivo = trim($request->input('motivo'));
            $prefijo = $tipo === 'disminuir' ? 'Ajuste manual (-): ' : 'Ajuste manual (+): ';

            $this->stockService->registrarMovimiento(
                $producto,
                $tipoMovimiento,
                $cantidad,
                $prefijo . $motivo,
                $idUsuario
            );

            $producto->refresh();
            $stock = $producto->stock;

            return response()->json([
                'status' => 'success',
                'message' => 'Ajuste de inventario registrado exitosamente.',
                'data' => [
                    'id_producto' => $producto->id_producto,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'tipo' => $tipo,
                    'cantidad_ajustada' => $cantidad,
                    'motivo' => $motivo,
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
