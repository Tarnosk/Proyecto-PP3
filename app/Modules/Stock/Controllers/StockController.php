<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Stock\Services\StockService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }
    /**
     * S01 - Consultar stock disponible de todos los productos.
     * Permite buscar por nombre o código y muestra la cantidad disponible actualizada.
     */
    public function consultaGeneral(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $estado = $request->input('estado'); // normal | bajo | critico | null (todos)

        $query = Producto::with(['categoria', 'marca'])
            ->where('estado', 'activo');

        if ($search && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('codigo', 'LIKE', "%{$term}%")
                  ->orWhere('descripcion', 'LIKE', "%{$term}%")
                  ->orWhere('nombre', 'LIKE', "%{$term}%");
            });
        }

        $stockMin = $request->input('stock_min');
        $stockMax = $request->input('stock_max');
        if ($stockMin !== null && $stockMin !== '') {
            $query->where('stock', '>=', (int) $stockMin);
        }
        if ($stockMax !== null && $stockMax !== '') {
            $query->where('stock', '<=', (int) $stockMax);
        }

        $productos = $query->orderBy('descripcion', 'asc')
            ->get()
            ->filter(function (Producto $p) use ($estado) {
                if (!$estado || !in_array($estado, ['normal', 'bajo', 'critico'], true)) {
                    return true;
                }
                return $p->estado_alerta === $estado;
            })
            ->values()
            ->map(fn (Producto $p) => $this->armarStock($p));

        return response()->json([
            'status' => 'success',
            'data' => $productos,
        ]);
    }

    /**
     * S01/S02 - Consultar stock actual de un producto concreto por su ID.
     * Incluye equivalencias de unidades configuradas (S08) y compatibilidad con G4 (HttpStockApi).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $producto = Producto::with('unidades')->find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        if ($producto->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto no se encuentra activo.',
            ], 400);
        }

        $stockActual = (int) $producto->stock;

        $unidades = $producto->unidades->map(function ($u) use ($stockActual) {
            $equivalencia = (float) $u->equivalencia_base;
            return [
                'id_unidad' => $u->id_unidad,
                'nombre_unidad' => $u->nombre_unidad,
                'equivalencia_base' => $equivalencia,
                'descripcion' => $u->descripcion,
                'disponible_en_unidad' => $this->disponibleEnUnidad($stockActual, $equivalencia),
            ];
        });

        return response()->json([
            'status' => 'success',
            'stock' => $stockActual,
            'disponible' => $stockActual,
            'data' => [
                'id' => $producto->id_producto,
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio_unitario' => (float) $producto->precio_unitario,
                'precio_mayorista' => (float) $producto->precio_mayorista,
                'precio_minorista' => (float) $producto->precio_minorista,
                'stock' => $stockActual,
                'stock_disponible' => $stockActual,
                'disponible' => $stockActual,
                'stock_minimo' => $producto->stock_minimo,
                'estado_alerta' => $producto->estado_alerta,
                'unidades' => $unidades,
            ],
        ]);
    }

    public function disponibilidadParaVenta(Request $request, int $id): JsonResponse
    {
        return $this->show($request, $id);
    }

    /**
     * S04 / Integración G4 - Registrar uno o múltiples movimientos de stock (POST /api/stock/movimientos).
     * Soporta el contrato de HttpStockApi::ajustar() con arreglo 'items' [{id_producto, cantidad}]
     * donde cantidad < 0 descuenta stock (venta) y cantidad > 0 repone stock (devolución/anulacion).
     */
    public function registrarMovimientosLote(Request $request): JsonResponse
    {
        $items = $request->input('items');
        if (!is_array($items) || empty($items)) {
            if ($request->filled('id_producto') && $request->filled('cantidad')) {
                $items = [[
                    'id_producto' => (int) $request->input('id_producto'),
                    'cantidad' => (int) $request->input('cantidad'),
                    'id_unidad' => $request->input('id_unidad'),
                    'tipo' => $request->input('tipo'),
                ]];
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Debe proporcionar al menos un ítem en items[] con id_producto y cantidad.',
                ], 400);
            }
        }

        $idUsuario = UsuarioActual::id($request);
        $idVenta = $request->filled('venta_id')
            ? (int) $request->input('venta_id')
            : ($request->filled('id_venta') ? (int) $request->input('id_venta') : null);
        if ($idVenta !== null && $idVenta <= 0) {
            $idVenta = null;
        }
        $origen = trim((string) $request->input('origen', 'ventas'));
        $motivoBase = trim((string) ($request->input('motivo') ?? 'Movimiento de stock por API'));

        try {
            $resultados = DB::transaction(function () use ($items, $idUsuario, $idVenta, $origen, $motivoBase) {
                $procesados = [];

                foreach ($items as $idx => $item) {
                    $idProducto = (int) ($item['id_producto'] ?? $item['producto_id'] ?? 0);
                    $cantidadRaw = (int) ($item['cantidad'] ?? 0);

                    if ($idProducto <= 0 || $cantidadRaw === 0) {
                        throw new \RuntimeException("Ítem #{$idx} inválido: requiere id_producto > 0 y cantidad distinta de 0.");
                    }

                    $producto = Producto::lockForUpdate()->find($idProducto);
                    if (!$producto || $producto->estado !== 'activo') {
                        throw new \RuntimeException("El producto #{$idProducto} no existe o se encuentra inactivo.");
                    }

                    $tipoExplicito = isset($item['tipo']) ? strtolower(trim((string) $item['tipo'])) : null;
                    if ($tipoExplicito && in_array($tipoExplicito, StockService::TIPOS, true)) {
                        $tipo = $tipoExplicito;
                        $esIncremento = in_array($tipo, ['ingreso', 'devolucion'], true) && $cantidadRaw > 0;
                    } else {
                        $esIncremento = $cantidadRaw > 0;
                        $tipo = $esIncremento
                            ? ($origen === 'ventas' ? 'devolucion' : 'ajuste')
                            : 'venta';
                    }

                    $cantidadAbs = abs($cantidadRaw);
                    $idUnidad = isset($item['id_unidad']) && (int) $item['id_unidad'] > 0 ? (int) $item['id_unidad'] : null;

                    $mov = $this->stockService->registrarMovimiento(
                        $producto,
                        $tipo,
                        $cantidadAbs,
                        $motivoBase !== '' ? $motivoBase : "Movimiento ({$tipo})",
                        $idUsuario,
                        $idUnidad,
                        $idVenta,
                        null,
                        null,
                        $esIncremento
                    );

                    $producto->refresh();

                    $procesados[] = [
                        'id_movimiento' => $mov->id_movimiento,
                        'id_producto' => $producto->id_producto,
                        'codigo' => $producto->codigo,
                        'tipo' => $tipo,
                        'cantidad' => $cantidadAbs,
                        'stock' => (int) $producto->stock,
                        'stock_disponible' => (int) $producto->stock,
                        'disponible' => (int) $producto->stock,
                    ];
                }

                return $procesados;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Movimientos de stock registrados correctamente.',
                'data' => $resultados,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * S12 - Historial de movimientos de un producto (ingresos, ventas, devoluciones, ajustes).
     */
    public function historial(Request $request, int $id): JsonResponse
    {
        $producto = Producto::with(['movimientos.unidad', 'movimientos.usuario'])->find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        $movimientos = $producto->movimientos->map(function ($m) {
            return [
                'id_movimiento' => $m->id_movimiento,
                'id_producto' => $m->id_producto,
                'tipo' => $m->tipo,
                'cantidad' => $m->cantidad,
                'cantidad_base' => $m->cantidad_base,
                'fecha' => $m->fecha ? $m->fecha->format('Y-m-d H:i:s') : null,
                'motivo' => $m->motivo,
                'id_usuario' => $m->id_usuario,
                'usuario_nombre' => $m->usuario ? $m->usuario->nombre : ($m->id_usuario ? "Usuario #{$m->id_usuario}" : 'Sistema'),
                'unidad' => $m->unidad ? $m->unidad->nombre_unidad : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'movimientos' => $movimientos,
            ],
        ]);
    }

    /**
     * S12 - Listado general de movimientos de stock (Kardex global y por producto).
     */
    public function todosLosMovimientos(Request $request): JsonResponse
    {
        $query = MovimientoStock::with(['producto', 'unidad', 'usuario'])
            ->orderBy('id_movimiento', 'desc');

        if ($request->filled('id_producto')) {
            $query->where('id_producto', (int) $request->input('id_producto'));
        }

        if ($request->filled('tipo') && in_array($request->input('tipo'), ['ingreso', 'venta', 'devolucion', 'ajuste'])) {
            $query->where('tipo', $request->input('tipo'));
        }

        $limite = (int) $request->input('limit', 150);
        $movimientos = $query->limit($limite)->get()->map(function ($m) {
            return [
                'id_movimiento' => $m->id_movimiento,
                'id_producto' => $m->id_producto,
                'producto_codigo' => $m->producto ? $m->producto->codigo : '-',
                'producto_nombre' => $m->producto ? ($m->producto->nombre ?? $m->producto->descripcion) : '-',
                'tipo' => $m->tipo,
                'cantidad' => $m->cantidad,
                'cantidad_base' => $m->cantidad_base,
                'fecha' => $m->fecha ? $m->fecha->format('Y-m-d H:i:s') : null,
                'motivo' => $m->motivo,
                'id_usuario' => $m->id_usuario,
                'usuario_nombre' => $m->usuario ? $m->usuario->nombre : ($m->id_usuario ? "Usuario #{$m->id_usuario}" : 'Sistema'),
                'unidad' => $m->unidad ? $m->unidad->nombre_unidad : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $movimientos,
        ]);
    }

    /**
     * Arma la estructura de respuesta para la consulta general de stock (S01).
     */
    private function armarStock(Producto $p): array
    {
        return [
            'id_producto' => $p->id_producto,
            'codigo' => $p->codigo,
            'descripcion' => $p->descripcion,
            'id_categoria' => $p->id_categoria,
            'categoria_nombre' => $p->categoria ? $p->categoria->nombre : null,
            'id_marca' => $p->id_marca,
            'marca_nombre' => $p->marca ? $p->marca->nombre : null,
            'stock' => (int) ($p->stock ?? $p->stock_disponible ?? 0),
            'stock_disponible' => (int) ($p->stock_disponible ?? $p->stock ?? 0),
            'stock_minimo' => (int) ($p->stock_minimo ?? 0),
            'estado_alerta' => $p->estado_alerta,
            'precio_unitario' => (float) $p->precio_unitario,
            'precio_mayorista' => (float) $p->precio_mayorista,
            'precio_minorista' => (float) $p->precio_minorista,
        ];
    }

    /**
     * Convierte el stock disponible (en unidad base = 1) a una unidad dada (S08).
     */
    private function disponibleEnUnidad(int $disponible, float $equivalencia): float
    {
        return (float) $disponible / $equivalencia;
    }
}
