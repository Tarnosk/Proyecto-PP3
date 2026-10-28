<?php
declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compras\Models\Compra;
use App\Modules\Compras\Models\DetalleCompra;
use App\Modules\Compras\Models\DetalleRecepcion;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Compras\Models\Recepcion;
use App\Modules\PedidosDeCompra\Models\OrdenCompra;
use App\Modules\Stock\Services\StockService;
use App\Support\UsuarioActual;
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

        if (!$request->has('items') && $request->has('detalles')) {
            $request->merge(['items' => $request->input('detalles')]);
        }

        $validator = Validator::make($request->all(), [
            'fecha_recepcion' => 'nullable|date|before_or_equal:today',
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

        // Normalizar la solicitud: preservar lotes y sumar cantidades por producto
        $itemsNormalizados = [];
        $totalPorProducto = [];
        foreach ($items as $item) {
            $idProducto = (int) ($item['id_producto'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? $item['cantidad_recibida'] ?? 0);

            if ($idProducto <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Cada ítem debe indicar un producto.'], 400);
            }
            if ($cantidad <= 0) {
                return response()->json(['status' => 'error', 'message' => 'La cantidad recibida debe ser mayor a cero.'], 400);
            }

            $totalPorProducto[$idProducto] = ($totalPorProducto[$idProducto] ?? 0) + $cantidad;
            $itemsNormalizados[] = [
                'id_producto' => $idProducto,
                'cantidad' => $cantidad,
            ];
        }

        // Mapear los detalles de la compra por producto.
        $detalles = $compra->detalles->keyBy('id_producto');

        // Validar cantidades contra los pendientes antes de la transacción
        foreach ($totalPorProducto as $idProducto => $cantTotal) {
            $detalle = $detalles->get($idProducto);
            if (!$detalle) {
                return response()->json([
                    'status' => 'error',
                    'message' => "El producto con ID {$idProducto} no forma parte de esta compra.",
                ], 400);
            }
            $pendiente = (int) $detalle->cantidad - (int) $detalle->cantidad_recibida;
            if ($cantTotal > $pendiente) {
                return response()->json([
                    'status' => 'error',
                    'message' => "La cantidad recibida del producto ID {$idProducto} excede el pendiente ({$pendiente}).",
                ], 400);
            }
        }

        $idUsuario = UsuarioActual::id($request);
        $fecha = $request->input('fecha_recepcion') ?? Carbon::now()->toDateString();

        try {
            $resultado = DB::transaction(function () use ($compra, $detalles, $itemsNormalizados, $idUsuario, $fecha) {
                $recepcion = Recepcion::create([
                    'id_compra' => $compra->id_compra,
                    'id_proveedor' => $compra->id_proveedor,
                    'fecha_recepcion' => $fecha,
                    'id_usuario' => $idUsuario,
                ]);

                $recibidos = [];

                foreach ($itemsNormalizados as $it) {
                    $idProducto = $it['id_producto'];
                    $cantidadRecibida = $it['cantidad'];
                    /** @var DetalleCompra $detalle */
                    $detalle = $detalles->get($idProducto);

                    $pendiente = (int) $detalle->cantidad - (int) $detalle->cantidad_recibida;

                    $producto = Producto::find($idProducto);
                    if (!$producto || $producto->estado !== 'activo') {
                        throw new RuntimeException("El producto con ID {$idProducto} no existe o no está activo.");
                    }

                    // Registrar detalle de recepción.
                    DetalleRecepcion::create([
                        'id_recepcion' => $recepcion->id_recepcion,
                        'id_producto' => $idProducto,
                        'id_unidad' => $detalle->id_unidad,
                        'cantidad_recibida' => $cantidadRecibida,
                    ]);

                    // Actualizar la cantidad recibida del detalle de compra.
                    $detalle->cantidad_recibida = (int) $detalle->cantidad_recibida + $cantidadRecibida;
                    $detalle->save();

                    // Sumar al stock como ingreso (C06 depende de S03).
                    $this->sumarStock($producto, $cantidadRecibida, $idUsuario, $detalle->id_unidad, $recepcion->id_recepcion, $fecha);

                    // PV05: Asociar automáticamente el producto al proveedor si aún no lo está
                    $asocProv = ProductoProveedor::where('id_producto', $idProducto)
                        ->where('id_proveedor', $compra->id_proveedor)
                        ->first();
                    $costoUnitario = (float) $detalle->precio_unitario;

                    if (!$asocProv) {
                        $tieneOtroPrincipal = ProductoProveedor::where('id_producto', $idProducto)
                            ->where('activo', true)
                            ->where('es_proveedor_principal', true)
                            ->exists();

                        ProductoProveedor::create([
                            'id_producto' => $idProducto,
                            'id_proveedor' => $compra->id_proveedor,
                            'es_proveedor_principal' => !$tieneOtroPrincipal,
                            'precio_acordado' => $costoUnitario,
                            'activo' => true,
                            'fecha_asociacion' => $fecha,
                            'id_usuario' => $idUsuario,
                        ]);
                    } else {
                        $upd = ['activo' => true, 'fecha_desasociacion' => null];
                        if ($asocProv->precio_acordado === null) {
                            $upd['precio_acordado'] = $costoUnitario;
                        }
                        $asocProv->update($upd);
                    }

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

                // Propagar avance de estado a la ORDEN_COMPRA asociada si existe (PC06)
                if ($compra->id_orden) {
                    $orden = OrdenCompra::find($compra->id_orden);
                    if ($orden && $orden->estado !== 'cancelada') {
                        $orden->estado = $todasCompletas ? 'completada' : 'recibida_parcialmente';
                        $orden->fecha_modificacion = Carbon::now()->toDateString();
                        $orden->save();
                    }
                }

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
                    'recibidos' => $resultado['recibidos'],
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
    private function sumarStock(
        Producto $producto,
        int $cantidad,
        int $idUsuario,
        ?int $idUnidad = null,
        ?int $idRecepcion = null,
        \Carbon\Carbon|string|null $fecha = null
    ): void {
        $this->stockService->registrarMovimiento(
            $producto,
            'ingreso',
            $cantidad,
            'Recepción de compra',
            $idUsuario,
            $idUnidad,
            null,
            $idRecepcion,
            $fecha,
            false
        );
    }
}
