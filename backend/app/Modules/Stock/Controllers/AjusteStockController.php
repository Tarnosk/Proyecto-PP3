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
            'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'fecha' => 'nullable|date|before_or_equal:today',
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

            $idUsuario = UsuarioActual::id($request);
            $cantidad = (int) $request->input('cantidad');
            $tipo = $request->input('tipo');

            // Mantener tipo 'ajuste' siempre para cumplir S12 (historial de ajustes).
            $tipoMovimiento = 'ajuste';
            $motivo = trim($request->input('motivo'));
            $prefijo = $tipo === 'disminuir' ? 'Ajuste manual (-): ' : 'Ajuste manual (+): ';
            $idUnidad = $request->has('id_unidad') ? (int) $request->input('id_unidad') : null;
            $fechaAjuste = null;
            if ($request->has('fecha') && $request->input('fecha')) {
                try {
                    $fechaAjuste = \Carbon\Carbon::parse($request->input('fecha'));
                } catch (\Throwable $e) {
                    $fechaAjuste = null;
                }
            }

            $this->stockService->registrarMovimiento(
                $producto,
                'ajuste',
                $cantidad,
                $prefijo . $motivo,
                $idUsuario,
                $idUnidad,
                null,
                null,
                $fechaAjuste
            );

            $producto->refresh();

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
