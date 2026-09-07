<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PedidosDeCompra\Models\DetalleOrden;
use App\Modules\PedidosDeCompra\Models\OrdenCompra;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * MÃ³dulo Ã“rdenes de Compra.
 *
 * PC01 - Generar Ã³rdenes de compra.
 * PC02 - Consultar Ã³rdenes de compra emitidas.
 * PC03 - Consultar el detalle de una orden de compra.
 * PC04 - Editar una orden de compra.
 * PC05 - Cancelar una orden de compra.
 * PC06 - Visualizar el estado de una orden de compra.
 */
class OrdenCompraController extends Controller
{
    /**
     * Formato de una orden (sin detalles).
     */
    private function formato(OrdenCompra $o): array
    {
        return [
            'id_orden' => $o->id_orden,
            'numero_orden' => $o->numero_orden,
            'id_proveedor' => $o->id_proveedor,
            'proveedor' => $o->proveedor?->razon_social,
            'total_estimado' => (float) $o->total_estimado,
            'estado' => $o->estado,
            'fecha_creacion' => $o->fecha_creacion ? $o->fecha_creacion->format('Y-m-d') : null,
            'fecha_modificacion' => $o->fecha_modificacion ? $o->fecha_modificacion->format('Y-m-d') : null,
            'fecha_envio' => $o->fecha_envio ? $o->fecha_envio->format('Y-m-d') : null,
            'fecha_cancelacion' => $o->fecha_cancelacion ? $o->fecha_cancelacion->format('Y-m-d') : null,
            'total_productos' => (int) $o->detalles()->sum('cantidad_solicitada'),
        ];
    }

    /**
     * Valida los Ã­tems de una orden. Devuelve null si es vÃ¡lido o un array de error.
     */
    private function validarItems(array $items): ?array
    {
        foreach ($items as $item) {
            if (!isset($item['id_producto']) || (int) $item['id_producto'] <= 0) {
                return ['status' => 'error', 'message' => 'Cada Ã­tem debe indicar un producto.', 'code' => 400];
            }
            $producto = Producto::find((int) $item['id_producto']);
            if (!$producto || $producto->estado !== 'activo') {
                return [
                    'status' => 'error',
                    'message' => "El producto con ID {$item['id_producto']} no existe o no estÃ¡ activo.",
                    'code' => 400,
                ];
            }
            if (!isset($item['cantidad_solicitada']) || (int) $item['cantidad_solicitada'] <= 0) {
                return ['status' => 'error', 'message' => 'Cada Ã­tem debe indicar una cantidad solicitada mayor a cero.', 'code' => 400];
            }
            if (!isset($item['precio_estimado']) || (float) $item['precio_estimado'] < 0) {
                return ['status' => 'error', 'message' => 'Cada Ã­tem debe indicar un precio estimado vÃ¡lido.', 'code' => 400];
            }
        }
        return null;
    }

    /**
     * Devuelve la unidad de medida del producto (desde su registro de stock, si existe).
     */
    private function unidadDe(Producto $producto): ?int
    {
        $stock = $producto->stock;
        return $stock?->id_unidad;
    }

    /**
     * PC01 - Generar una orden de compra.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_proveedor' => 'required|integer',
            'fecha_creacion' => 'nullable|date|before_or_equal:today',
            'id_usuario' => 'nullable|integer',
            'items' => 'required|array|min:1',
        ], [
            'id_proveedor.required' => 'Debe seleccionar un proveedor.',
            'fecha_creacion.before_or_equal' => 'La fecha de creaciÃ³n no puede ser posterior a la fecha actual.',
            'items.required' => 'Debe agregar al menos un producto a la orden.',
            'items.min' => 'Debe agregar al menos un producto a la orden.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $proveedor = Proveedor::find((int) $request->input('id_proveedor'));
        if (!$proveedor) {
            return response()->json(['status' => 'error', 'message' => 'El proveedor seleccionado no existe.'], 404);
        }
        if ($proveedor->estado !== 'activo') {
            return response()->json(['status' => 'error', 'message' => 'No se puede crear una orden con un proveedor inactivo. (PV04)'], 400);
        }

        $items = $request->input('items');
        $error = $this->validarItems($items);
        if ($error) {
            return response()->json(['status' => $error['status'], 'message' => $error['message']], $error['code']);
        }

        $idUsuario = (int) $request->input('id_usuario', 1);
        $fecha = $request->input('fecha_creacion') ?? Carbon::now()->toDateString();

        try {
            $orden = DB::transaction(function () use ($request, $proveedor, $items, $idUsuario, $fecha) {
                $totalEstimado = 0;
                $detalles = [];

                foreach ($items as $item) {
                    $cantidad = (int) $item['cantidad_solicitada'];
                    $precio = (float) $item['precio_estimado'];
                    $subtotal = round($cantidad * $precio, 2);
                    $totalEstimado += $subtotal;
                    $producto = Producto::find((int) $item['id_producto']);

                    $detalles[] = [
                        'id_producto' => (int) $item['id_producto'],
                        'id_unidad' => $this->unidadDe($producto),
                        'cantidad_solicitada' => $cantidad,
                        'cantidad_sugerida' => isset($item['cantidad_sugerida']) ? (int) $item['cantidad_sugerida'] : $cantidad,
                        'origen' => $item['origen'] ?? 'manual',
                        'precio_estimado' => $precio,
                        'subtotal' => $subtotal,
                    ];
                }

                $totalEstimado = round($totalEstimado, 2);

                $orden = OrdenCompra::create([
                    'numero_orden' => $this->generarNumero(),
                    'id_proveedor' => $proveedor->id_proveedor,
                    'total_estimado' => $totalEstimado,
                    'estado' => 'pendiente',
                    'fecha_creacion' => $fecha,
                    'id_usuario' => $idUsuario,
                ]);

                foreach ($detalles as $d) {
                    DetalleOrden::create(array_merge($d, ['id_orden' => $orden->id_orden]));
                }

                return $orden;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Orden de compra generada exitosamente.',
                'data' => $this->formato($orden),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar la orden: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera un nÃºmero Ãºnico de orden (PC01): OC-AAAA-nnnnnn.
     */
    private function generarNumero(): string
    {
        do {
            $numero = 'OC-' . Carbon::now()->format('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (OrdenCompra::where('numero_orden', $numero)->exists());

        return $numero;
    }

    /**
     * PC02 - Listar y filtrar Ã³rdenes por proveedor y rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrdenCompra::with('proveedor');

        if ($request->filled('id_proveedor')) {
            $query->where('id_proveedor', (int) $request->input('id_proveedor'));
        }
        if ($request->filled('desde')) {
            $query->whereDate('fecha_creacion', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_creacion', '<=', $request->input('hasta'));
        }

        $estado = $request->input('estado');
        if ($estado && $estado !== 'todos' && in_array($estado, OrdenCompra::ESTADOS, true)) {
            $query->where('estado', $estado);
        }

        $ordenes = $query->orderByDesc('fecha_creacion')->orderByDesc('id_orden')->get();

        return response()->json([
            'status' => 'success',
            'data' => $ordenes->map(fn (OrdenCompra $o) => $this->formato($o)),
        ], 200);
    }

    /**
     * PC03 / PC06 - Detalle de una orden (productos, cantidades, estado).
     */
    public function show(int $id): JsonResponse
    {
        $orden = OrdenCompra::with(['proveedor', 'detalles.producto'])->find($id);
        if (!$orden) {
            return response()->json(['status' => 'error', 'message' => 'La orden de compra no existe.'], 404);
        }

        $detalles = $orden->detalles->map(function (DetalleOrden $d) {
            return [
                'id_detalle_orden' => $d->id_detalle_orden,
                'id_producto' => $d->id_producto,
                'codigo' => $d->producto?->codigo,
                'descripcion' => $d->producto?->descripcion,
                'cantidad_solicitada' => (int) $d->cantidad_solicitada,
                'precio_estimado' => (float) $d->precio_estimado,
                'subtotal' => (float) $d->subtotal,
                'origen' => $d->origen,
            ];
        });

        $totalSolicitado = (int) $orden->detalles->sum('cantidad_solicitada');

        return response()->json([
            'status' => 'success',
            'data' => [
                'orden' => $this->formato($orden),
                'total_solicitado' => $totalSolicitado,
                'detalles' => $detalles,
            ],
        ], 200);
    }

    /**
     * PC04 - Editar una orden pendiente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $orden = OrdenCompra::find($id);
        if (!$orden) {
            return response()->json(['status' => 'error', 'message' => 'La orden de compra no existe.'], 404);
        }

        if ($orden->estado !== 'pendiente') {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo se pueden editar Ã³rdenes pendientes. Una vez enviada no puede modificarse. (PC04)',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'fecha_creacion' => 'nullable|date|before_or_equal:today',
            'items' => 'required|array|min:1',
        ], [
            'fecha_creacion.before_or_equal' => 'La fecha no puede ser posterior a la fecha actual.',
            'items.required' => 'Debe agregar al menos un producto a la orden.',
            'items.min' => 'Debe agregar al menos un producto a la orden.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $items = $request->input('items');
        $error = $this->validarItems($items);
        if ($error) {
            return response()->json(['status' => $error['status'], 'message' => $error['message']], $error['code']);
        }

        try {
            $orden = DB::transaction(function () use ($orden, $items, $request) {
                $totalEstimado = 0;
                $nuevos = [];

                foreach ($items as $item) {
                    $cantidad = (int) $item['cantidad_solicitada'];
                    $precio = (float) $item['precio_estimado'];
                    $subtotal = round($cantidad * $precio, 2);
                    $totalEstimado += $subtotal;
                    $producto = Producto::find((int) $item['id_producto']);

                    $nuevos[] = [
                        'id_producto' => (int) $item['id_producto'],
                        'id_unidad' => $this->unidadDe($producto),
                        'cantidad_solicitada' => $cantidad,
                        'cantidad_sugerida' => isset($item['cantidad_sugerida']) ? (int) $item['cantidad_sugerida'] : $cantidad,
                        'origen' => $item['origen'] ?? 'manual',
                        'precio_estimado' => $precio,
                        'subtotal' => $subtotal,
                    ];
                }

                $totalEstimado = round($totalEstimado, 2);

                $orden->detalles()->delete();
                foreach ($nuevos as $n) {
                    DetalleOrden::create(array_merge($n, ['id_orden' => $orden->id_orden]));
                }

                $orden->total_estimado = $totalEstimado;
                $orden->fecha_modificacion = Carbon::now()->toDateString();
                $orden->save();

                return $orden;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Orden de compra editada exitosamente.',
                'data' => $this->formato($orden),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al editar la orden: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PC05 - Cancelar una orden pendiente.
     */
    public function cancelar(int $id): JsonResponse
    {
        $orden = OrdenCompra::find($id);
        if (!$orden) {
            return response()->json(['status' => 'error', 'message' => 'La orden de compra no existe.'], 404);
        }

        if ($orden->estado !== 'pendiente') {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo se pueden cancelar Ã³rdenes pendientes. Una orden ya completada no puede cancelarse. (PC05)',
            ], 400);
        }

        $orden->estado = 'cancelada';
        $orden->fecha_cancelacion = Carbon::now()->toDateString();
        $orden->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Orden de compra cancelada. Se conserva el historial.',
            'data' => $this->formato($orden),
        ], 200);
    }

    /**
     * AcciÃ³n complementaria: enviar una orden pendiente (necesaria para PC04/PC05/PC06).
     * Marca la fecha de envÃ­o y pasa la orden a estado 'enviada'.
     */
    public function enviar(Request $request, int $id): JsonResponse
    {
        $orden = OrdenCompra::find($id);
        if (!$orden) {
            return response()->json(['status' => 'error', 'message' => 'La orden de compra no existe.'], 404);
        }

        if ($orden->estado !== 'pendiente') {
            return response()->json(['status' => 'error', 'message' => 'Solo se pueden enviar Ã³rdenes pendientes.'], 400);
        }

        $fechaEnv = $request->input('fecha_envio') ?? Carbon::now()->toDateString();
        $orden->estado = 'enviada';
        $orden->fecha_envio = $fechaEnv;
        $orden->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Orden de compra enviada al proveedor.',
            'data' => $this->formato($orden),
        ], 200);
    }
}
