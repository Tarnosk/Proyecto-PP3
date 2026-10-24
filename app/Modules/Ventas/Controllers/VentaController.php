<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ventas\Contracts\ClienteGateway;
use App\Modules\Ventas\Contracts\ProductoGateway;
use App\Services\MockUsuarioApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function index(ClienteGateway $clientes, ProductoGateway $productos)
    {
        return view('ventas', [
            'productos' => $productos->todos(),
            'clientes' => $clientes->todos(),
        ]);
    }

    public function obtenerProductosSimulados(ProductoGateway $productos)
    {
        return response()->json($productos->todos());
    }

    public function store(Request $request, ClienteGateway $clientes, ProductoGateway $productos)
    {
        $todosLosProductos = $productos->todos();
        $datos = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'lista' => ['required', Rule::in(['minorista', 'mayorista'])],
            'observaciones' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', Rule::in(array_column($todosLosProductos, 'id'))],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.descuento' => ['required', 'numeric', 'min:0'],
            'descuento_global' => ['sometimes', 'array'],
            'descuento_global.modo' => ['required_with:descuento_global', Rule::in(['porcentaje', 'monto'])],
            'descuento_global.valor' => ['required_with:descuento_global', 'numeric', 'min:0'],
        ]);
        $cliente = $clientes->buscar((int) $datos['cliente_id']);

        if ($cliente === null) {
            throw ValidationException::withMessages([
                'cliente_id' => ['Seleccioná un cliente válido.'],
            ]);
        }
        $productosIndexados = collect($todosLosProductos)->keyBy('id');
        $lista = $datos['lista'] === 'mayorista' ? 'precioMay' : 'precioMin';
        foreach ($datos['items'] as $indice => $item) {
            $producto = $productosIndexados->get((int) $item['id']);
            if ((float) $item['descuento'] > (float) $producto[$lista]) {
                throw ValidationException::withMessages([
                    "items.{$indice}.descuento" => ['El descuento no puede superar el precio unitario actual.'],
                ]);
            }
        }

        $detalles = collect($datos['items'])->map(function (array $item) use ($productosIndexados, $lista): array {
            $producto = $productosIndexados->get((int) $item['id']);
            $precio = (int) $producto[$lista];
            $descuento = round((float) $item['descuento'], 2);
            $subtotal = round((int) $item['cantidad'] * ($precio - $descuento), 2);

            return [
                'id_producto' => $producto['id'],
                'cantidad' => (int) $item['cantidad'],
                'precio_unitario' => $precio,
                'descuento' => $descuento,
                'subtotal' => $subtotal,
            ];
        });
        $subtotalConDescuentosDeLinea = round($detalles->sum('subtotal'), 2);
        $descuentoGlobal = $datos['descuento_global'] ?? ['modo' => 'monto', 'valor' => 0];
        $valorDescuentoGlobal = (float) $descuentoGlobal['valor'];

        if ($descuentoGlobal['modo'] === 'porcentaje' && $valorDescuentoGlobal > 100) {
            throw ValidationException::withMessages([
                'descuento_global.valor' => ['El porcentaje de descuento global debe estar entre 0 y 100.'],
            ]);
        }

        $montoDescuentoGlobal = $descuentoGlobal['modo'] === 'porcentaje'
            ? round($subtotalConDescuentosDeLinea * $valorDescuentoGlobal / 100, 2)
            : round(min($valorDescuentoGlobal, $subtotalConDescuentosDeLinea), 2);
        $total = round($subtotalConDescuentosDeLinea - $montoDescuentoGlobal, 2);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'items' => ['La venta debe tener un total mayor a cero.'],
            ]);
        }

        $observaciones = trim($datos['observaciones'] ?? '') ?: 'Venta POS a cuenta corriente';

        $ventaId = DB::transaction(function () use ($cliente, $detalles, $total, $montoDescuentoGlobal, $observaciones): int {
            $ventaId = DB::table('venta')->insertGetId([
                'id_cliente' => $cliente['id'],
                'fecha' => now()->toDateString(),
                'total' => $total,
                'descuento_global' => $montoDescuentoGlobal,
                'numFactura' => 'POS-'.Str::ulid(),
                'estado' => 'confirmada',
                'observaciones' => $observaciones,
                'id_usuario' => config('cuenta_corriente.id_usuario_prueba'),
            ], 'id_venta');

            foreach ($detalles as $detalle) {
                DB::table('detalle_venta')->insert([
                    'id_venta' => $ventaId,
                    'id_producto' => $detalle['id_producto'],
                    'id_promocion' => null,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'descuento' => $detalle['descuento'],
                    'subtotal' => $detalle['subtotal'],
                ]);
            }

            DB::table('cuenta_corriente_movimientos')->insert([
                'cliente_id' => $cliente['id'],
                'venta_id' => $ventaId,
                'tipo' => 'venta',
                'descripcion' => "Venta POS #{$ventaId}",
                'importe' => -$total,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (int) $ventaId;
        });

        return response()->json([
            'mensaje' => 'Venta registrada y cargada a la cuenta corriente.',
            'venta_id' => $ventaId,
            'cliente_id' => $cliente['id'],
            'descuento_global' => $montoDescuentoGlobal,
            'total' => $total,
        ], 201);
    }

    public function historial(ClienteGateway $clientes, MockUsuarioApi $usuarios, ProductoGateway $productos)
    {
        $ventas = DB::table('venta as v')
            ->select(
                'v.id_venta',
                'v.id_cliente',
                'v.numFactura as factura',
                'v.fecha',
                'v.total',
                'v.descuento_global as descuento',
                'v.estado',
                'v.observaciones',
                'v.id_usuario'
            )
            ->orderByDesc('v.fecha')
            ->orderByDesc('v.id_venta')
            ->get();

        $clientesMock = collect($clientes->todos())->keyBy('id');
        $productosMock = collect($productos->todos())
        ->keyBy('id')
        ->all();
        $idsVentas = $ventas->pluck('id_venta')->all();
        $detalles = empty($idsVentas)
            ? collect()
            : DB::table('detalle_venta as dv')
                ->whereIn('dv.id_venta', $idsVentas)
                ->select(
                    'dv.id_venta',
                    'dv.id_producto',
                    'dv.cantidad',
                    'dv.precio_unitario',
                    'dv.descuento',
                    'dv.subtotal'
                )
                ->get()
                ->groupBy('id_venta');


        $metodosPago = collect();

        if (! empty($idsVentas)) {
            try {
                $metodosPago = DB::table('cobro_venta as cv')
                    ->join('cobro as c', 'c.id_cobro', '=', 'cv.id_cobro')
                    ->whereIn('cv.id_venta', $idsVentas)
                    ->select('cv.id_venta', 'c.medio_pago')
                    ->get()
                    ->groupBy('id_venta')
                    ->map(fn ($grupo) => $grupo->pluck('medio_pago')->unique()->values()->all());
            } catch (\Throwable $e) {
                $metodosPago = collect();
            }
        }

        $ventas = $ventas->map(function ($venta) use (
            $detalles,
            $metodosPago,
            $clientesMock,
            $usuarios,
            $productosMock
        ) {
            $cliente = $clientesMock->get($venta->id_cliente);

            $detalle = ($detalles->get($venta->id_venta) ?? collect())
                ->map(function ($item) use ($productosMock) {
                    $producto = $productosMock[$item->id_producto] ?? null;

                    return [
                        'id_producto' => (int) $item->id_producto,
                        'codigo' => $producto['codigo'] ?? '',
                        'producto' => $producto['nombre'] ?? "Producto #{$item->id_producto}",
                        'cantidad' => (int) $item->cantidad,
                        'precio_unitario' => (float) $item->precio_unitario,
                        'descuento' => (float) $item->descuento,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })->all();

            $medios = $metodosPago->get($venta->id_venta, []);
            $esCC = empty($medios);

            return [
                'id' => (int) $venta->id_venta,
                'cliente_id' => (int) $venta->id_cliente,
                'factura' => $venta->factura ?? ('#' . $venta->id_venta),
                'fecha' => $venta->fecha,
                'fecha_iso' => substr((string) $venta->fecha, 0, 10),
                'cliente' => $cliente['nombre'] ?? "Cliente #{$venta->id_cliente}",
                'localidad' => $cliente['localidad'] ?? '',
                'total' => (float) $venta->total,
                'descuento' => (float) ($venta->descuento ?? 0)
                    + collect($detalle)->sum(fn ($item) => $item['descuento'] * $item['cantidad']),
                'estado' => $venta->estado ?? 'pendiente',
                'observaciones' => $venta->observaciones,
                'usuario' => $usuarios->nombre((int) $venta->id_usuario),
                'metodo' => $esCC
                    ? 'Cuenta Corriente'
                    : ucfirst(str_replace('_', ' ', $medios[0])),
                'cc' => $esCC,

                // Conserva las acciones que ya tenía develop-g4.
                ...\App\Modules\Ventas\Enums\EstadoVenta::accionesPara($venta->estado),

                'detalle' => $detalle,
            ];
        })->values();


        $kpis = [
            'total_vendido' => $ventas->sum('total'),
            'descuentos' => $ventas->sum('descuento'),
            'en_cc' => $ventas->where('cc', true)->sum('total'),
            'cantidad' => $ventas->count(),
        ];
        $clientesFiltro = collect($clientes->todos())
            ->map(fn ($cliente) => ['id' => $cliente['id'], 'nombre' => $cliente['nombre']])
            ->values();
        $productosFiltro = collect($productos->todos())
            ->map(fn ($producto) => ['id_producto' => $producto['id'], 'nombre' => $producto['nombre']])
            ->values();

        return view('historial-ventas', compact(
            'ventas', 'kpis', 'clientesFiltro', 'productosFiltro'
        ));
    }
}
