<?php
declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compras\Models\Compra;
use App\Modules\Compras\Models\DetalleCompra;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Stock\Models\UnidadMedida;
use App\Modules\Compras\Models\Recepcion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Support\UsuarioActual;

/**
 * Módulo Compras.
 *
 * C01 - Registrar compras de mercadería.
 * C02 - Consultar compras realizadas.
 * C03 - Consultar el detalle de una compra.
 * C04 - Modificar una compra pendiente.
 * C05 - Cancelar una compra pendiente.
 */
class CompraController extends Controller
{
    /**
     * Formato de una compra (sin detalles).
     */
    private function formato(Compra $c): array
    {
        return [
            'id_compra' => $c->id_compra,
            'numero_compra' => $c->numero_compra,
            'numero_comprobante' => $c->numero_comprobante,
            'id_proveedor' => $c->id_proveedor,
            'proveedor' => $c->proveedor?->razon_social,
            'importe_total' => (float) $c->importe_total,
            'saldo_pendiente' => (float) $c->saldo_pendiente,
            'estado' => $c->estado,
            'fecha_compra' => $c->fecha_compra ? $c->fecha_compra->format('Y-m-d') : null,
            'fecha_vencimiento' => $c->fecha_vencimiento ? $c->fecha_vencimiento->format('Y-m-d') : null,
            'fecha_cancelacion' => $c->fecha_cancelacion ? $c->fecha_cancelacion->format('Y-m-d') : null,
            'total_productos' => $c->detalles()->sum('cantidad'),
        ];
    }

    /**
     * Valida los ítems de una compra. Devuelve null si es válido o un array
     * con la respuesta de error.
     */
    private function validarItems(array $items): ?array
    {
        foreach ($items as $item) {
            if (!isset($item['id_producto']) || (int) $item['id_producto'] <= 0) {
                return ['status' => 'error', 'message' => 'Cada ítem debe indicar un producto.', 'code' => 400];
            }
            $producto = Producto::find((int) $item['id_producto']);
            if (!$producto || $producto->estado !== 'activo') {
                return [
                    'status' => 'error',
                    'message' => "El producto con ID {$item['id_producto']} no existe o no está activo.",
                    'code' => 400,
                ];
            }
            if (!isset($item['cantidad']) || (int) $item['cantidad'] <= 0) {
                return ['status' => 'error', 'message' => 'Cada ítem debe indicar una cantidad mayor a cero.', 'code' => 400];
            }
            if (!isset($item['precio_unitario']) || (float) $item['precio_unitario'] < 0) {
                return ['status' => 'error', 'message' => 'Cada ítem debe indicar un precio unitario válido.', 'code' => 400];
            }
        }
        return null;
    }

    /**
     * C01 - Registrar una compra de mercadería.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_proveedor' => 'required|integer',
            'fecha_compra' => 'nullable|date|before_or_equal:today',
            'numero_comprobante' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
        ], [
            'id_proveedor.required' => 'Debe seleccionar un proveedor.',
            'fecha_compra.before_or_equal' => 'La fecha de la compra no puede ser posterior a la fecha actual.',
            'numero_comprobante.max' => 'El número de comprobante no puede superar los 50 caracteres.',
            'items.required' => 'Debe agregar al menos un producto a la compra.',
            'items.min' => 'Debe agregar al menos un producto a la compra.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $proveedor = Proveedor::find((int) $request->input('id_proveedor'));
        if (!$proveedor) {
            return response()->json(['status' => 'error', 'message' => 'El proveedor seleccionado no existe.'], 404);
        }
        if ($proveedor->estado !== 'activo') {
            return response()->json(['status' => 'error', 'message' => 'No se puede crear una compra con un proveedor inactivo.'], 400);
        }

        $items = $request->input('items');

        $error = $this->validarItems($items);
        if ($error) {
            return response()->json(['status' => $error['status'], 'message' => $error['message']], $error['code']);
        }

        $idUsuario = UsuarioActual::id($request);
        $fecha = $request->input('fecha_compra') ?? Carbon::now()->toDateString();

        try {
            $compra = DB::transaction(function () use ($request, $proveedor, $items, $idUsuario, $fecha) {
                $importeTotal = 0;
                $detalles = [];

                foreach ($items as $item) {
                    $cantidad = (int) $item['cantidad'];
                    $precio = (float) $item['precio_unitario'];
                    $subtotal = round($cantidad * $precio, 2);
                    $importeTotal += $subtotal;
                    $detalles[] = [
                        'id_producto' => (int) $item['id_producto'],
                        'id_unidad' => $this->unidadDelItem($item),
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                    ];
                }

                $importeTotal = round($importeTotal, 2);

                $compra = Compra::create([
                    'numero_compra' => $this->generarComprobante(),
                    'numero_comprobante' => $request->input('numero_comprobante') ?: null,
                    'id_proveedor' => $proveedor->id_proveedor,
                    'importe_total' => $importeTotal,
                    'saldo_pendiente' => $importeTotal,
                    'estado' => 'pendiente',
                    'fecha_compra' => $fecha,
                    'id_usuario' => $idUsuario,
                ]);

                foreach ($detalles as $d) {
                    DetalleCompra::create([
                        'id_compra' => $compra->id_compra,
                        'id_producto' => $d['id_producto'],
                        'id_unidad' => $d['id_unidad'],
                        'cantidad' => $d['cantidad'],
                        'cantidad_recibida' => 0,
                        'precio_unitario' => $d['precio_unitario'],
                        'subtotal' => $d['subtotal'],
                    ]);
                }

                return $compra;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Compra registrada exitosamente.',
                'data' => $this->formato($compra),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar la compra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DETALLE_COMPRA.id_unidad es NOT NULL en el esquema fusionado. Cuando el item
     * no indica unidad se usa la unidad base del producto, que es la que asume
     * cantidad y precio_unitario.
     */
    private function unidadDelItem(array $item): int
    {
        $idUnidad = isset($item['id_unidad']) ? (int) $item['id_unidad'] : 0;

        if ($idUnidad > 0) {
            return $idUnidad;
        }

        $idProducto = (int) ($item['id_producto'] ?? 0);
        $base = $idProducto > 0
            ? UnidadMedida::where('id_producto', $idProducto)->where('es_base', 1)->value('id_unidad')
            : null;

        return (int) ($base ?? UnidadMedida::where('es_base', 1)->orderBy('id_unidad')->value('id_unidad'));
    }

    /**
     * Genera un identificador único de comprobante (C01): NCC-AAAA-000000.
     */
    private function generarComprobante(): string    {
        do {
            $numero = 'NCC-' . Carbon::now()->format('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Compra::where('numero_compra', $numero)->exists());

        return $numero;
    }

    /**
     * C02 - Listar y filtrar compras por proveedor y rango de fechas.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Compra::with('proveedor');

        if ($request->filled('id_proveedor')) {
            $query->where('id_proveedor', (int) $request->input('id_proveedor'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_compra', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_compra', '<=', $request->input('hasta'));
        }

        $estado = $request->input('estado');
        if ($estado && in_array($estado, Compra::ESTADOS, true)) {
            $query->where('estado', $estado);
        }

        $compras = $query->orderByDesc('fecha_compra')->orderByDesc('id_compra')->get();

        return response()->json([
            'status' => 'success',
            'data' => $compras->map(fn (Compra $c) => $this->formato($c)),
        ], 200);
    }

    /**
     * C03 - Consultar el detalle de una compra.
     */
    public function show(int $id): JsonResponse
    {
        $compra = Compra::with(['proveedor', 'detalles.producto', 'recepciones.detalles.producto'])->find($id);
        if (!$compra) {
            return response()->json(['status' => 'error', 'message' => 'La compra no existe.'], 404);
        }

        $totalRecibido = $compra->detalles()->sum('cantidad_recibida');
        $totalComprado = $compra->detalles()->sum('cantidad');

        $detalles = $compra->detalles->map(function (DetalleCompra $d) {
            return [
                'id_detalle' => $d->id_detalle_compra,
                'id_producto' => $d->id_producto,
                'codigo' => $d->producto?->codigo,
                'descripcion' => $d->producto?->descripcion,
                'cantidad' => (int) $d->cantidad,
                'cantidad_recibida' => (int) $d->cantidad_recibida,
                'cantidad_pendiente' => max(0, (int) $d->cantidad - (int) $d->cantidad_recibida),
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ];
        });

        $recepciones = $compra->recepciones->map(function (Recepcion $r) {
            return [
                'id_recepcion' => $r->id_recepcion,
                'fecha_recepcion' => $r->fecha_recepcion ? $r->fecha_recepcion->format('Y-m-d') : null,
                'total_recibido' => (int) $r->detalles->sum('cantidad_recibida'),
                'productos' => $r->detalles->map(fn ($dr) => [
                    'id_producto' => $dr->id_producto,
                    'codigo' => $dr->producto?->codigo,
                    'descripcion' => $dr->producto?->descripcion,
                    'cantidad_recibida' => (int) $dr->cantidad_recibida,
                ]),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'compra' => $this->formato($compra),
                'total_comprado' => (int) $totalComprado,
                'total_recibido' => (int) $totalRecibido,
                'total_pendiente' => max(0, (int) $totalComprado - (int) $totalRecibido),
                'pagos' => $this->datosPagos($compra),
                'detalles' => $detalles,
                'recepciones' => $recepciones,
            ],
        ], 200);
    }

    /**
     * C07 - Visualizar el estado de una compra.
     *
     * Endpoint dedicado: devuelve el estado y el avance en unidades y en dinero,
     * sin el detalle completo de items y recepciones que trae show().
     *
     * Ojo con los dos saldos: saldo_pendiente es el importe de mercadería todavía
     * no recibida (lo calcula RecepcionController, C06), mientras saldo_a_pagar es
     * la deuda real = importe_total - total_pagado (C08).
     */
    public function estado(int $id): JsonResponse
    {
        $compra = Compra::with(['proveedor'])->find($id);
        if (!$compra) {
            return response()->json(['status' => 'error', 'message' => 'La compra no existe.'], 404);
        }

        $comprado = (int) $compra->detalles()->sum('cantidad');
        $recibido = (int) $compra->detalles()->sum('cantidad_recibida');
        $pendiente = max(0, $comprado - $recibido);
        $pagos = $this->datosPagos($compra);

        $porProducto = $compra->detalles()
            ->with('producto:id_producto,codigo,descripcion')
            ->get()
            ->map(fn (DetalleCompra $d) => [
                'id_producto' => $d->id_producto,
                'codigo' => $d->producto?->codigo,
                'descripcion' => $d->producto?->descripcion,
                'comprado' => (int) $d->cantidad,
                'recibido' => (int) $d->cantidad_recibida,
                'pendiente' => max(0, (int) $d->cantidad - (int) $d->cantidad_recibida),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id_compra' => $compra->id_compra,
                'numero_compra' => $compra->numero_compra,
                'numero_comprobante' => $compra->numero_comprobante,
                'proveedor' => $compra->proveedor?->razon_social,
                'estado' => $compra->estado,
                'unidades' => [
                    'compradas' => $comprado,
                    'recibidas' => $recibido,
                    'pendientes' => $pendiente,
                    'porcentaje_recibido' => $comprado > 0 ? round($recibido * 100 / $comprado, 2) : 0.0,
                ],
                'importes' => [
                    'importe_total' => round((float) $compra->importe_total, 2),
                    // Mercadería aún no recibida (C06)
                    'saldo_pendiente' => round((float) $compra->saldo_pendiente, 2),
                    // Deuda real por pagos (C08)
                    'total_pagado' => $pagos['total_pagado'],
                    'saldo_a_pagar' => $pagos['saldo_a_pagar'],
                ],
                'pagos' => $pagos,
                'por_producto' => $porProducto,
            ],
        ], 200);
    }

    /** Datos de pago de la compra (C08), reutilizados por show() y estado(). */
    private function datosPagos(Compra $compra): array
    {
        $totalPagado = $compra->totalPagado();
        $importeTotal = round((float) $compra->importe_total, 2);

        return [
            'cantidad' => $compra->pagos()->count(),
            'total_pagado' => $totalPagado,
            'saldo_a_pagar' => max(0.0, round($importeTotal - $totalPagado, 2)),
            'completos' => $importeTotal - $totalPagado <= 0,
            'por_metodo' => $compra->pagos()
                ->selectRaw('metodo_pago, count(*) as cantidad_pagos, sum(importe) as total')
                ->groupBy('metodo_pago')
                ->orderBy('metodo_pago')
                ->get()
                ->map(fn ($r) => [
                    'metodo_pago' => $r->metodo_pago,
                    'cantidad_pagos' => (int) $r->cantidad_pagos,
                    'total' => round((float) $r->total, 2),
                ]),
        ];
    }

    /**
     * C04 - Modificar una compra pendiente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $compra = Compra::find($id);
        if (!$compra) {
            return response()->json(['status' => 'error', 'message' => 'La compra no existe.'], 404);
        }

        if ($compra->estado !== 'pendiente') {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo se pueden modificar compras pendientes. (C04)',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'fecha_compra' => 'nullable|date|before_or_equal:today',
            'numero_comprobante' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
        ], [
            'fecha_compra.before_or_equal' => 'La fecha de la compra no puede ser posterior a la fecha actual.',
            'numero_comprobante.max' => 'El número de comprobante no puede superar los 50 caracteres.',
            'items.required' => 'Debe agregar al menos un producto a la compra.',
            'items.min' => 'Debe agregar al menos un producto a la compra.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $items = $request->input('items');
        $error = $this->validarItems($items);
        if ($error) {
            return response()->json(['status' => $error['status'], 'message' => $error['message']], $error['code']);
        }

        $idUsuario = UsuarioActual::id($request);

        try {
            $compra = DB::transaction(function () use ($compra, $items, $idUsuario, $request) {
                $importeTotal = 0;
                $nuevos = [];

                foreach ($items as $item) {
                    $cantidad = (int) $item['cantidad'];
                    $precio = (float) $item['precio_unitario'];
                    $subtotal = round($cantidad * $precio, 2);
                    $importeTotal += $subtotal;
                    $nuevos[] = [
                        'id_producto' => (int) $item['id_producto'],
                        'id_unidad' => $this->unidadDelItem($item),
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                    ];
                }

                $importeTotal = round($importeTotal, 2);

                $compra->detalles()->delete();

                foreach ($nuevos as $n) {
                    DetalleCompra::create([
                        'id_compra' => $compra->id_compra,
                        'id_producto' => $n['id_producto'],
                        'id_unidad' => $n['id_unidad'],
                        'cantidad' => $n['cantidad'],
                        'cantidad_recibida' => 0,
                        'precio_unitario' => $n['precio_unitario'],
                        'subtotal' => $n['subtotal'],
                    ]);
                }

                if ($request->filled('fecha_compra')) {
                    $compra->fecha_compra = $request->input('fecha_compra');
                }
                if ($request->has('numero_comprobante')) {
                    $compra->numero_comprobante = $request->input('numero_comprobante') ?: null;
                }
                $compra->importe_total = $importeTotal;
                $compra->saldo_pendiente = $importeTotal;

                $compra->save();

                return $compra;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Compra modificada exitosamente.',
                'data' => $this->formato($compra),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al modificar la compra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * C05 - Cancelar una compra pendiente.
     */
    public function cancelar(Request $request, int $id): JsonResponse
    {
        $compra = Compra::find($id);
        if (!$compra) {
            return response()->json(['status' => 'error', 'message' => 'La compra no existe.'], 404);
        }

        if ($compra->estado !== 'pendiente') {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo se pueden cancelar compras pendientes. (C05)',
            ], 400);
        }

        $compra->estado = 'cancelada';
        $compra->fecha_cancelacion = Carbon::now()->toDateString();
        $compra->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Compra cancelada. Se conserva el historial.',
            'data' => $this->formato($compra),
        ], 200);
    }
}
