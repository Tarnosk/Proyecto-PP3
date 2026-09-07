<?php
declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compras\Models\Compra;
use App\Modules\Compras\Models\DetalleCompra;
use App\Modules\Compras\Models\DetalleRecepcion;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Productos\Models\Producto;
use App\Modules\Compras\Models\Recepcion;
use App\Modules\Stock\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * C06 - Registrar recepciones parciales de una compra.
 *
 * Permite registrar entregas incompletas del proveedor. Cada recepción puede
 * cubrir parte o todo el saldo pendiente de cada producto. Al recibir se
 * actualiza el stock y el estado de la compra (parcialmente_recibida/completada).
 */
class RecepcionController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * C06 - Registrar una recepción (parcial o total) de una compra.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $compra = Compra::with('detalles')->find($id);
        if (!$compra) {
            return response()->json(['status' => 'error', 'message' => 'La compra no existe.'], 404);
        }

        if ($compra->estado === 'cancelada') {
            return response()->json(['status' => 'error', 'message' => 'No se puede recibir una compra cancelada.'], 400);
        }
        if ($compra->estado === 'completada') {
            return response()->json(['status' => 'error', 'message' => 'La compra ya fue recibida por completo.'], 400);
        }

        if ($compra->proveedor && $compra->proveedor->estado !== 'activo') {
            return response()->json(['status' => 'error', 'message' => 'No se puede recibir de un proveedor inactivo. (PV04)'], 400);
        }

        $validator = Validator::make($request->all(), [
            'fecha_recepcion' => 'nullable|date|before_or_equal:today',
            'id_usuario' => 'nullable|integer',
            'items' => 'required|array|min:1',
        ], [
            'fecha_recepcion.before_or_equal' => 'La fecha de recepción no puede ser posterior a la fecha actual.',
            'items.required' => 'Debe indicar al menos un producto recibido.',
            'items.min' => 'Debe indicar al menos un producto recibido.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $items = $request->input('items');

        // Normalizar la solicitud: sumar cantidades repetidas del mismo producto.
        $solicitado = [];
        foreach ($items as $item) {
            $idProducto = (int) ($item['id_producto'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? $item['cantidad_recibida'] ?? 0);

            if ($idProducto <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Cada ítem debe indicar un producto.'], 400);
            }
            if ($cantidad <= 0) {
                return response()->json(['status' => 'error', 'message' => 'La cantidad recibida debe ser mayor a cero.'], 400);
            }

            $solicitado[$idProducto] = ($solicitado[$idProducto] ?? 0) + $cantidad;
        }

        // Mapear los detalles de la compra por producto.
        $detalles = $compra->detalles->keyBy('id_producto');

        $idUsuario = (int) $request->input('id_usuario', 1);
        $fecha = $request->input('fecha_recepcion') ?? Carbon::now()->toDateString();

        try {
            $resultado = DB::transaction(function () use ($compra, $detalles, $solicitado, $idUsuario, $fecha) {
                $recepcion = Recepcion::create([
                    'id_compra' => $compra->id_compra,
                    'id_proveedor' => $compra->id_proveedor,
                    'fecha_recepcion' => $fecha,
                    'id_usuario' => $idUsuario,
                ]);

                $recibidos = [];

                foreach ($solicitado as $idProducto => $cantidadRecibida) {
                    /** @var DetalleCompra|null $detalle */
                    $detalle = $detalles->get($idProducto);
                    if (!$detalle) {
                        throw new RuntimeException("El producto con ID {$idProducto} no forma parte de esta compra.");
                    }

                    $pendiente = (int) $detalle->cantidad - (int) $detalle->cantidad_recibida;
                    if ($cantidadRecibida > $pendiente) {
                        throw new RuntimeException(
                            "La cantidad recibida del producto ID {$idProducto} excede el pendiente ({$pendiente})."
                        );
                    }

                    $producto = Producto::find($idProducto);
                    if (!$producto || $producto->estado !== 'activo') {
                        throw new RuntimeException("El producto con ID {$idProducto} no existe o no está activo.");
                    }

                    // Registrar detalle de recepción.
                    DetalleRecepcion::create([
                        'id_recepcion' => $recepcion->id_recepcion,
                        'id_producto' => $idProducto,
                        'id_lote' => null,
                        'id_unidad' => $detalle->id_unidad,
                        'cantidad_recibida' => $cantidadRecibida,
                    ]);

                    // Actualizar la cantidad recibida del detalle de compra.
                    $detalle->cantidad_recibida = (int) $detalle->cantidad_recibida + $cantidadRecibida;
                    $detalle->save();

                    // Sumar al stock como ingreso (C06 depende de S03).
                    $this->sumarStock($producto, $cantidadRecibida, $idUsuario);

                    $recibidos[] = [
                        'id_producto' => $producto->id_producto,
                        'codigo' => $producto->codigo,
                        'descripcion' => $producto->descripcion,
                        'cantidad_recibida' => $cantidadRecibida,
                        'cantidad_pendiente' => max(0, $pendiente - $cantidadRecibida),
                    ];
                }

                // Recalcular estado y saldo según el total recibido de TODA la compra (C06).
                $compra->refresh();
                $compra->load('detalles');
                $todos = $compra->detalles->filter(fn ($d) => (int) $d->cantidad > 0);
                $saldoTotal = (int) $todos->sum('cantidad');
                $recibidoTotal = (int) $todos->sum('cantidad_recibida');
                $pendienteTotal = max(0, $saldoTotal - $recibidoTotal);

                $todasCompletas = $recibidoTotal >= $saldoTotal;
                $compra->estado = $todasCompletas ? 'completada' : 'parcialmente_recibida';

                if ($saldoTotal > 0) {
                    $compra->saldo_pendiente = round($compra->importe_total * $pendienteTotal / $saldoTotal, 2);
                }

                $compra->save();

                return [
                    'recepcion' => $recepcion,
                    'recibidos' => $recibidos,
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Recepción registrada exitosamente.',
                'data' => [
                    'id_recepcion' => $resultado['recepcion']->id_recepcion,
                    'id_compra' => $compra->id_compra,
                    'fecha_recepcion' => $resultado['recepcion']->fecha_recepcion->format('Y-m-d'),
                    'estado_compra' => $compra->estado,
                    'items' => $resultado['recibidos'],
                ],
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar la recepción: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suma stock de un producto como ingreso (S03) y recalcula su alerta.
     * Se ejecuta dentro de la transacción de la recepción (C06).
     */
    private function sumarStock(Producto $producto, int $cantidad, int $idUsuario): void
    {
        $stock = $producto->stock;
        if (!$stock) {
            throw new RuntimeException("El producto {$producto->codigo} no tiene registro de stock.");
        }

        $stock->stock_disponible = (int) $stock->stock_disponible + $cantidad;
        $stock->save();

        $this->stockService->recalcularAlerta($stock);

        MovimientoStock::create([
            'id_producto' => $producto->id_producto,
            'id_unidad' => $stock->id_unidad,
            'tipo' => 'ingreso',
            'cantidad' => $cantidad,
            'fecha' => Carbon::now()->toDateString(),
            'motivo' => 'Recepción de compra (C06)',
            'id_usuario' => $idUsuario,
        ]);
    }
}
