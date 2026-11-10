<?php
declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compras\Models\Compra;
use App\Modules\Compras\Models\DetalleCompra;
use App\Modules\Compras\Models\DetalleRecepcion;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Stock\Models\UnidadMedida;
use App\Modules\Stock\Services\StockService;
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
        $importeTotal = round((float) $c->importe_total, 2);
        $totalPagado = $c->relationLoaded('pagos')
            ? round((float) $c->pagos->sum('importe'), 2)
            : $c->totalPagado();
        $saldoAPagar = $c->estado === 'cancelada'
            ? 0.0
            : max(0.0, round($importeTotal - $totalPagado, 2));

        if ($c->estado === 'cancelada') {
            $estadoPago = 'cancelada';
        } elseif ($saldoAPagar <= 0 && $importeTotal > 0) {
            $estadoPago = 'pagada';
        } elseif ($totalPagado > 0) {
            $estadoPago = 'pago_parcial';
        } else {
            $estadoPago = 'pendiente_pago';
        }

        return [
            'id_compra' => $c->id_compra,
            'numero_compra' => $c->numero_compra,
            'numero_comprobante' => $c->numero_comprobante,
            'id_orden' => $c->id_orden ? (int) $c->id_orden : null,
            'numero_orden' => $c->orden?->numero_orden,
            'id_proveedor' => $c->id_proveedor,
            'proveedor' => $c->proveedor?->razon_social,
            'proveedor_cuit' => $c->proveedor?->cuit,
            'proveedor_direccion' => $c->proveedor?->direccion,
            'proveedor_telefono' => $c->proveedor?->telefono,
            'proveedor_email' => $c->proveedor?->email,
            'importe_total' => $importeTotal,
            'saldo_pendiente' => (float) $c->saldo_pendiente,
            'total_pagado' => $totalPagado,
            'saldo_a_pagar' => $saldoAPagar,
            'estado_pago' => $estadoPago,
            'estado' => $c->estado,
            'cierre_parcial' => (bool) ($c->cierre_parcial ?? false),
            'observaciones' => $c->observaciones ?? null,
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
            if (isset($item['cantidad_recibida'])) {
                $rec = (int) $item['cantidad_recibida'];
                if ($rec < 0) {
                    return ['status' => 'error', 'message' => 'La cantidad a recibir no puede ser negativa.', 'code' => 400];
                }
                if ($rec > (int) $item['cantidad']) {
                    return ['status' => 'error', 'message' => 'La cantidad a recibir no puede superar la cantidad facturada.', 'code' => 400];
                }
            }
        }
        return null;
    }

    /**
     * C01 - Registrar una compra de mercadería.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->has('items') && $request->has('detalles')) {
            $request->merge(['items' => $request->input('detalles')]);
        }
        if (!$request->has('fecha_compra') && $request->has('fecha')) {
            $request->merge(['fecha_compra' => $request->input('fecha')]);
        }
        if (!$request->has('numero_comprobante') && $request->has('numero_factura')) {
            $request->merge(['numero_comprobante' => $request->input('numero_factura')]);
        }

        $validator = Validator::make($request->all(), [
            'id_proveedor' => 'required|integer',
            'id_orden' => 'nullable|integer|exists:ORDEN_COMPRA,id_orden',
            'fecha_compra' => 'nullable|date|before_or_equal:today',
            'fecha_vencimiento' => 'nullable|date',
            'numero_comprobante' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
        ], [
            'id_proveedor.required' => 'Debe seleccionar un proveedor.',
            'id_orden.exists' => 'La orden de compra vinculada no existe.',
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
        $idOrden = $request->filled('id_orden') ? (int) $request->input('id_orden') : null;
        $recibirAhora = $request->boolean('recibir_ahora', false);

        if ($idOrden) {
            $ordenCheck = \App\Modules\PedidosDeCompra\Models\OrdenCompra::find($idOrden);
            if (!$ordenCheck) {
                return response()->json(['status' => 'error', 'message' => 'La orden de compra vinculada no existe.'], 404);
            }
            if ((int) $ordenCheck->id_proveedor !== (int) $proveedor->id_proveedor) {
                return response()->json(['status' => 'error', 'message' => 'La orden de compra vinculada pertenece a otro proveedor.'], 400);
            }
            if ($ordenCheck->estado === 'cancelada') {
                return response()->json(['status' => 'error', 'message' => 'No se puede vincular una orden de compra cancelada.'], 400);
            }
            if ($ordenCheck->estado === 'completada') {
                return response()->json(['status' => 'error', 'message' => 'No se puede vincular una orden de compra que ya ha sido completada.'], 400);
            }
            if ($ordenCheck->estado === 'borrador') {
                return response()->json(['status' => 'error', 'message' => 'No se puede vincular una orden de compra en estado borrador.'], 400);
            }
        }

        try {
            $compra = DB::transaction(function () use ($request, $proveedor, $items, $idUsuario, $fecha, $idOrden, $recibirAhora) {
                $importeTotal = 0;
                $detalles = [];
                $totalComprado = 0;
                $totalRecibido = 0;

                foreach ($items as $item) {
                    $cantidad = (int) $item['cantidad'];
                    $precio = (float) $item['precio_unitario'];
                    $subtotal = round($cantidad * $precio, 2);
                    $importeTotal += $subtotal;

                    $cantRecibir = 0;
                    if ($recibirAhora) {
                        $cantRecibir = isset($item['cantidad_recibida'])
                            ? min($cantidad, max(0, (int) $item['cantidad_recibida']))
                            : $cantidad;
                    }

                    $totalComprado += $cantidad;
                    $totalRecibido += $cantRecibir;

                    $detalles[] = [
                        'id_producto' => (int) $item['id_producto'],
                        'id_unidad' => $this->unidadDelItem($item),
                        'cantidad' => $cantidad,
                        'cantidad_recibida' => $cantRecibir,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal,
                    ];
                }

                $importeTotal = round($importeTotal, 2);
                $fechaVenc = $request->input('fecha_vencimiento') ?: Carbon::parse($fecha)->addDays(30)->toDateString();

                if (!$recibirAhora || $totalRecibido === 0) {
                    $estadoCompra = 'pendiente';
                    $saldoPendiente = $importeTotal;
                } elseif ($totalRecibido >= $totalComprado) {
                    $estadoCompra = 'completada';
                    $saldoPendiente = 0.0;
                } else {
                    $estadoCompra = 'parcialmente_recibida';
                    $saldoPendiente = 0.0;
                    foreach ($detalles as $det) {
                        $pendItem = max(0, (int) $det['cantidad'] - (int) $det['cantidad_recibida']);
                        $saldoPendiente += $pendItem * (float) $det['precio_unitario'];
                    }
                    $saldoPendiente = round($saldoPendiente, 2);
                }

                $compra = Compra::create([
                    'numero_compra' => $this->generarComprobante(),
                    'numero_comprobante' => $request->input('numero_comprobante') ?: null,
                    'id_proveedor' => $proveedor->id_proveedor,
                    'id_orden' => $idOrden,
                    'importe_total' => $importeTotal,
                    'saldo_pendiente' => $saldoPendiente,
                    'estado' => $estadoCompra,
                    'cierre_parcial' => false,
                    'fecha_compra' => $fecha,
                    'fecha_vencimiento' => $fechaVenc,
                    'id_usuario' => $idUsuario,
                ]);

                $recepcion = null;
                $stockService = ($totalRecibido > 0) ? app(StockService::class) : null;
                if ($totalRecibido > 0) {
                    $recepcion = Recepcion::create([
                        'id_compra' => $compra->id_compra,
                        'id_proveedor' => $proveedor->id_proveedor,
                        'fecha_recepcion' => $fecha,
                        'id_usuario' => $idUsuario,
                    ]);
                }

                foreach ($detalles as $d) {
                    DetalleCompra::create([
                        'id_compra' => $compra->id_compra,
                        'id_producto' => $d['id_producto'],
                        'id_unidad' => $d['id_unidad'],
                        'cantidad' => $d['cantidad'],
                        'cantidad_recibida' => $d['cantidad_recibida'],
                        'precio_unitario' => $d['precio_unitario'],
                        'subtotal' => $d['subtotal'],
                    ]);

                    if ($d['cantidad_recibida'] > 0 && $recepcion && $stockService) {
                        DetalleRecepcion::create([
                            'id_recepcion' => $recepcion->id_recepcion,
                            'id_producto' => $d['id_producto'],
                            'id_unidad' => $d['id_unidad'],
                            'cantidad_recibida' => $d['cantidad_recibida'],
                        ]);

                        $prodStock = Producto::find($d['id_producto']);
                        if ($prodStock) {
                            $stockService->registrarMovimiento(
                                $prodStock,
                                'ingreso',
                                (int) $d['cantidad_recibida'],
                                'Ingreso directo por compra ' . $compra->numero_compra,
                                $idUsuario,
                                $d['id_unidad'],
                                null,
                                $recepcion->id_recepcion,
                                $fecha,
                                false
                            );
                        }
                    }

                    // PV05: Asociar automáticamente el producto al proveedor al registrar una compra
                    // y actualizar el precio_acordado con el costo facturado.
                    $precioCompra = (float) $d['precio_unitario'];
                    $asocProv = ProductoProveedor::where('id_producto', $d['id_producto'])
                        ->where('id_proveedor', $proveedor->id_proveedor)
                        ->first();
                    if (!$asocProv) {
                        $tieneOtroPrincipal = ProductoProveedor::where('id_producto', $d['id_producto'])
                            ->where('activo', true)
                            ->where('es_proveedor_principal', true)
                            ->exists();

                        $asocProv = ProductoProveedor::create([
                            'id_producto' => $d['id_producto'],
                            'id_proveedor' => $proveedor->id_proveedor,
                            'es_proveedor_principal' => !$tieneOtroPrincipal,
                            'precio_acordado' => $precioCompra,
                            'activo' => true,
                            'fecha_asociacion' => $fecha,
                            'id_usuario' => $idUsuario,
                        ]);
                    } else {
                        $updAsoc = [
                            'activo' => true,
                            'fecha_desasociacion' => null,
                            'id_usuario' => $idUsuario,
                        ];
                        if ($precioCompra > 0) {
                            $updAsoc['precio_acordado'] = $precioCompra;
                        }
                        $asocProv->update($updAsoc);
                    }

                    // PV06: Registrar snapshot de precio en el historial del proveedor vinculado a la compra
                    if ($asocProv) {
                        app(\App\Modules\Proveedores\Services\HistorialPrecioService::class)->registrarSnapshot(
                            $asocProv->id_producto_proveedor,
                            $precioCompra,
                            $idUsuario,
                            $compra->id_compra,
                            $d['id_unidad'],
                            $fecha
                        );

                        // Si el proveedor de la compra es el proveedor principal activo,
                        // sincronizar costo de reposición y recalcular precio de venta automáticamente.
                        if ($asocProv->es_proveedor_principal && $precioCompra > 0) {
                            $prodCompra = Producto::find($d['id_producto']);
                            if ($prodCompra) {
                                $prodCompra->sincronizarCostoReposicion($idUsuario);
                            }
                        }
                    }
                }

                if ($idOrden) {
                    $ordenVinculada = \App\Modules\PedidosDeCompra\Models\OrdenCompra::find($idOrden);
                    if ($ordenVinculada) {
                        if ($ordenVinculada->estado === 'pendiente') {
                            $ordenVinculada->estado = 'enviada';
                            $ordenVinculada->fecha_envio = Carbon::now()->toDateString();
                            $ordenVinculada->save();
                        }
                        $ordenVinculada->sincronizarEstadoRecepcion();
                    }
                }

                return $compra->load(['proveedor', 'orden', 'pagos']);
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
        if ($idProducto <= 0) {
            throw new \InvalidArgumentException('Cada ítem debe indicar un producto válido para asignar su unidad de medida.');
        }

        $base = UnidadMedida::where('id_producto', $idProducto)
            ->where('es_base', 1)
            ->first();

        if ($base) {
            return (int) $base->id_unidad;
        }

        $base = UnidadMedida::where('id_producto', $idProducto)
            ->where('equivalencia_base', 1)
            ->orderBy('id_unidad')
            ->first();

        if ($base) {
            $base->es_base = true;
            $base->save();

            return (int) $base->id_unidad;
        }

        return (int) UnidadMedida::create([
            'id_producto' => $idProducto,
            'nombre_unidad' => 'Unidad individual',
            'equivalencia_base' => 1,
            'es_base' => true,
            'descripcion' => 'Unidad base del producto',
        ])->id_unidad;
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
        $query = Compra::with(['proveedor', 'orden', 'pagos']);

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
     * C09 - Visualizar deudas con proveedores (Issue #42).
     *
     * Consulta las compras no canceladas que tengan saldo pendiente de pago
     * (importe_total - total_pagado > 0), con detalle agrupado por proveedor,
     * compras pendientes y fechas de vencimiento próximas.
     */
    public function deudas(Request $request): JsonResponse
    {
        $query = Compra::with(['proveedor', 'pagos'])
            ->where('estado', '!=', 'cancelada');

        if ($request->filled('id_proveedor')) {
            $query->where('id_proveedor', (int) $request->input('id_proveedor'));
        }
        if ($request->filled('desde')) {
            $query->whereDate('fecha_compra', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_compra', '<=', $request->input('hasta'));
        }

        $compras = $query->orderBy('fecha_vencimiento', 'asc')
            ->orderBy('fecha_compra', 'desc')
            ->get();

        $deudasPorProveedor = [];
        $totalDeudaGeneral = 0.0;
        $todasComprasConDeuda = [];

        foreach ($compras as $c) {
            $totalPagado = round((float) $c->pagos->sum('importe'), 2);
            $importeTotal = round((float) $c->importe_total, 2);
            $saldoAPagar = max(0.0, round($importeTotal - $totalPagado, 2));

            if ($saldoAPagar <= 0) {
                continue;
            }

            $totalDeudaGeneral += $saldoAPagar;
            $idProv = $c->id_proveedor;

            $diasParaVencer = null;
            $estaVencida = false;
            if ($c->fecha_vencimiento) {
                $hoy = Carbon::today();
                $venc = Carbon::parse($c->fecha_vencimiento);
                $diasParaVencer = (int) $hoy->diffInDays($venc, false);
                $estaVencida = $diasParaVencer < 0;
            }

            $itemCompra = [
                'id_compra' => $c->id_compra,
                'numero_compra' => $c->numero_compra,
                'numero_comprobante' => $c->numero_comprobante,
                'fecha_compra' => $c->fecha_compra ? $c->fecha_compra->format('Y-m-d') : null,
                'fecha_vencimiento' => $c->fecha_vencimiento ? $c->fecha_vencimiento->format('Y-m-d') : null,
                'importe_total' => $importeTotal,
                'total_pagado' => $totalPagado,
                'saldo_a_pagar' => $saldoAPagar,
                'estado' => $c->estado,
                'dias_para_vencer' => $diasParaVencer,
                'esta_vencida' => $estaVencida,
                'proveedor' => $c->proveedor?->razon_social,
                'id_proveedor' => $idProv,
            ];

            $todasComprasConDeuda[] = $itemCompra;

            if (!isset($deudasPorProveedor[$idProv])) {
                $deudasPorProveedor[$idProv] = [
                    'id_proveedor' => $idProv,
                    'razon_social' => $c->proveedor?->razon_social ?? "Proveedor #{$idProv}",
                    'cuit' => $c->proveedor?->cuit,
                    'telefono' => $c->proveedor?->telefono,
                    'email' => $c->proveedor?->email,
                    'saldo_adeudado' => 0.0,
                    'total_adeudado' => 0.0,
                    'cantidad_compras' => 0,
                    'proximo_vencimiento' => null,
                    'compras' => [],
                ];
            }

            $nuevoSaldo = round($deudasPorProveedor[$idProv]['saldo_adeudado'] + $saldoAPagar, 2);
            $deudasPorProveedor[$idProv]['saldo_adeudado'] = $nuevoSaldo;
            $deudasPorProveedor[$idProv]['total_adeudado'] = $nuevoSaldo;
            $deudasPorProveedor[$idProv]['cantidad_compras']++;
            $deudasPorProveedor[$idProv]['compras'][] = $itemCompra;

            if ($c->fecha_vencimiento) {
                $fechaV = $c->fecha_vencimiento->format('Y-m-d');
                if ($deudasPorProveedor[$idProv]['proximo_vencimiento'] === null || $fechaV < $deudasPorProveedor[$idProv]['proximo_vencimiento']) {
                    $deudasPorProveedor[$idProv]['proximo_vencimiento'] = $fechaV;
                }
            }
        }

        $listaProveedores = collect(array_values($deudasPorProveedor))
            ->sortByDesc('saldo_adeudado')
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'saldo_adeudado_total' => round($totalDeudaGeneral, 2),
                'proveedores_con_deuda' => count($listaProveedores),
                'compras_con_deuda' => count($todasComprasConDeuda),
                'por_proveedor' => $listaProveedores,
                'proveedores' => $listaProveedores,
                'compras_pendientes' => $todasComprasConDeuda,
            ],
        ], 200);
    }

    /**
     * C03 - Consultar el detalle de una compra.
     */
    public function show(int $id): JsonResponse
    {
        $compra = Compra::with(['proveedor', 'orden', 'pagos', 'detalles.producto', 'recepciones.detalles.producto'])->find($id);
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
                'nombre' => $d->producto?->nombre,
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
                    'nombre' => $dr->producto?->nombre,
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
            'lista' => $compra->pagos()
                ->orderBy('fecha_pago', 'asc')
                ->orderBy('id_pago', 'asc')
                ->get()
                ->map(fn ($p) => \App\Modules\Compras\Services\PagoCompraService::formatear($p))
                ->toArray(),
        ];
    }

    /**
     * C04 - Modificar una compra pendiente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $compra = Compra::with('proveedor')->find($id);
        if (!$compra) {
            return response()->json(['status' => 'error', 'message' => 'La compra no existe.'], 404);
        }

        if ($compra->estado !== 'pendiente') {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo se pueden modificar compras pendientes. (C04)',
            ], 400);
        }

        if ($compra->proveedor && $compra->proveedor->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede modificar una compra de un proveedor inactivo. (PV04)',
            ], 400);
        }

        if (!$request->has('items') && $request->has('detalles')) {
            $request->merge(['items' => $request->input('detalles')]);
        }
        if (!$request->has('fecha_compra') && $request->has('fecha')) {
            $request->merge(['fecha_compra' => $request->input('fecha')]);
        }
        if (!$request->has('numero_comprobante') && $request->has('numero_factura')) {
            $request->merge(['numero_comprobante' => $request->input('numero_factura')]);
        }

        $validator = Validator::make($request->all(), [
            'id_orden' => 'nullable|integer|exists:ORDEN_COMPRA,id_orden',
            'fecha_compra' => 'nullable|date|before_or_equal:today',
            'fecha_vencimiento' => 'nullable|date',
            'numero_comprobante' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
        ], [
            'id_orden.exists' => 'La orden de compra vinculada no existe.',
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

                if ($request->has('id_orden')) {
                    $compra->id_orden = $request->filled('id_orden') ? (int) $request->input('id_orden') : null;
                }
                if ($request->filled('fecha_compra')) {
                    $compra->fecha_compra = $request->input('fecha_compra');
                }
                if ($request->has('fecha_vencimiento')) {
                    $compra->fecha_vencimiento = $request->input('fecha_vencimiento') ?: null;
                }
                if ($request->has('numero_comprobante')) {
                    $compra->numero_comprobante = $request->input('numero_comprobante') ?: null;
                }
                $compra->importe_total = $importeTotal;
                $compra->saldo_pendiente = $importeTotal;
                $compra->fecha_modificacion = Carbon::now();
                $compra->id_usuario_modificacion = $idUsuario;

                $compra->save();

                if ($compra->id_orden) {
                    $ordenVinculada = \App\Modules\PedidosDeCompra\Models\OrdenCompra::find($compra->id_orden);
                    if ($ordenVinculada && $ordenVinculada->estado === 'pendiente') {
                        $ordenVinculada->estado = 'enviada';
                        $ordenVinculada->fecha_envio = $ordenVinculada->fecha_envio ?: Carbon::now()->toDateString();
                        $ordenVinculada->fecha_modificacion = Carbon::now()->toDateString();
                        $ordenVinculada->save();
                    }
                }

                return $compra->load(['proveedor', 'orden', 'pagos']);
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
     * C05 - Cancelar / Anular una compra.
     * Si la compra ya había ingresado mercadería al stock, revierte automáticamente
     * las cantidades recibidas y resincroniza el estado de la Orden de Compra vinculada.
     */
    public function cancelar(Request $request, int $id): JsonResponse
    {
        $compra = Compra::with(['detalles', 'pagos'])->find($id);
        if (!$compra) {
            return response()->json(['status' => 'error', 'message' => 'La compra no existe.'], 404);
        }

        if ($compra->estado === 'cancelada') {
            return response()->json([
                'status' => 'error',
                'message' => 'La compra ya se encuentra cancelada. (C05)',
            ], 400);
        }

        $totalPagado = (float) $compra->totalPagado();
        if ($totalPagado > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede cancelar una compra que ya registra pagos ($' . number_format($totalPagado, 2, ',', '.') . '). Debe gestionar primero la reversión de los pagos.',
            ], 400);
        }

        // Validar previamente que el stock actual en depósito alcance para revertir lo recibido
        foreach ($compra->detalles as $detalle) {
            $recibido = (int) $detalle->cantidad_recibida;
            if ($recibido > 0) {
                $producto = Producto::find($detalle->id_producto);
                if ($producto) {
                    $stockActual = (int) ($producto->stock_disponible ?? $producto->stock ?? 0);
                    if ($stockActual < $recibido) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "No se puede cancelar la compra: el producto '{$producto->descripcion}' tiene un stock actual de {$stockActual}, insuficiente para revertir las {$recibido} unidades recibidas (posible stock ya vendido o consumido).",
                        ], 400);
                    }
                }
            }
        }

        $idUsuario = UsuarioActual::id($request);

        try {
            DB::transaction(function () use ($compra, $idUsuario) {
                $stockService = app(StockService::class);
                $hoy = Carbon::now()->toDateString();

                foreach ($compra->detalles as $detalle) {
                    $recibido = (int) $detalle->cantidad_recibida;
                    if ($recibido > 0) {
                        $producto = Producto::find($detalle->id_producto);
                        if ($producto) {
                            $stockService->registrarMovimiento(
                                $producto,
                                'ajuste',
                                $recibido,
                                'Reversión de stock por anulación de compra ' . ($compra->numero_compra ?: '#' . $compra->id_compra),
                                $idUsuario,
                                $detalle->id_unidad,
                                null,
                                null,
                                $hoy,
                                false
                            );
                        }
                        $detalle->cantidad_recibida = 0;
                        $detalle->save();
                    }
                }

                $compra->estado = 'cancelada';
                $compra->fecha_cancelacion = $hoy;
                $compra->save();

                if ($compra->id_orden) {
                    $orden = \App\Modules\PedidosDeCompra\Models\OrdenCompra::find($compra->id_orden);
                    if ($orden) {
                        $orden->sincronizarEstadoRecepcion();
                    }
                }
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al cancelar la compra: ' . $e->getMessage(),
            ], 400);
        }

        $compra->refresh()->load(['proveedor', 'orden', 'pagos']);

        return response()->json([
            'status' => 'success',
            'message' => 'Compra cancelada exitosamente. Se conserva el historial.',
            'data' => $this->formato($compra),
        ], 200);
    }

    /**
     * Cierra y da por completada una compra parcialmente recibida ajustando
     * las cantidades e importe total facturado a lo efectivamente ingresado a stock,
     * recalculando la deuda y el saldo pendiente.
     */
    public function cerrarParcial(int $id): JsonResponse
    {
        $compra = Compra::with(['detalles', 'pagos', 'orden'])->find($id);
        if (!$compra) {
            return response()->json(['status' => 'error', 'message' => 'La compra no existe.'], 404);
        }

        if ($compra->estado === 'cancelada') {
            return response()->json(['status' => 'error', 'message' => 'La compra ya se encuentra cancelada.'], 400);
        }

        if ($compra->estado === 'completada') {
            return response()->json(['status' => 'error', 'message' => 'La compra ya se encuentra completada.'], 400);
        }

        $totalRecibido = (int) $compra->detalles->sum('cantidad_recibida');
        if ($totalRecibido <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede dar por completada una compra sin mercadería recibida. Si no va a ingresar stock, cancele la compra.',
            ], 400);
        }

        $valorRecibido = 0.0;
        foreach ($compra->detalles as $d) {
            $valorRecibido += (int) $d->cantidad_recibida * (float) $d->precio_unitario;
        }
        $valorRecibido = round($valorRecibido, 2);

        $totalPagado = (float) $compra->totalPagado();
        if ($totalPagado > $valorRecibido) {
            $exceso = round($totalPagado - $valorRecibido, 2);
            return response()->json([
                'status' => 'error',
                'message' => "No se puede dar por completada la compra: el importe ya pagado ($" . number_format($totalPagado, 2, ',', '.') . ") supera el valor de lo efectivamente recibido ($" . number_format($valorRecibido, 2, ',', '.') . ") en $" . number_format($exceso, 2, ',', '.') . ". Al no gestionarse notas de crédito en el sistema, debe regularizar o revertir el pago excedente antes de cerrar la compra.",
            ], 400);
        }

        DB::transaction(function () use ($compra) {
            foreach ($compra->detalles as $d) {
                $rec = (int) $d->cantidad_recibida;
                $d->cantidad = $rec;
                $d->subtotal = round($rec * (float) $d->precio_unitario, 2);
                $d->save();
            }

            $nuevoTotal = round((float) $compra->detalles()->sum('subtotal'), 2);
            $compra->importe_total = $nuevoTotal;
            $compra->saldo_pendiente = 0.0;
            $compra->estado = 'completada';
            $compra->cierre_parcial = true;
            $compra->observaciones = trim(($compra->observaciones ? $compra->observaciones . "\n" : '') . 'Comprobante cerrado por recepción parcial definitiva. Saldo no entregado cancelado.');
            $compra->fecha_modificacion = Carbon::now()->toDateString();
            $compra->save();

            if ($compra->id_orden) {
                $orden = \App\Modules\PedidosDeCompra\Models\OrdenCompra::find($compra->id_orden);
                if ($orden) {
                    $orden->estado = 'completada';
                    $orden->cierre_parcial = true;
                    $orden->observaciones = trim(($orden->observaciones ? $orden->observaciones . "\n" : '') . 'Orden de compra cerrada por recepción parcial definitiva.');
                    $orden->fecha_modificacion = Carbon::now()->toDateString();
                    $orden->save();
                }
            }
        });

        $compra->refresh()->load(['proveedor', 'orden', 'pagos']);

        return response()->json([
            'status' => 'success',
            'message' => 'Compra cerrada y dada por completada con lo efectivamente recibido. La deuda y el saldo fueron recalculados.',
            'data' => $this->formato($compra),
        ], 200);
    }
}
