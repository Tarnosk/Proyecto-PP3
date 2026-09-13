<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
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
                  ->orWhere('descripcion', 'LIKE', "%{$term}%");
            });
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
     * S02 - Disponibilidad para venta de un producto concreto.
     * Incluye conversión entre unidades configuradas (S08) para ventas minoristas/mayoristas.
     */
    public function disponibilidadParaVenta(Request $request, int $id): JsonResponse
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
                'message' => 'El producto no se encuentra activo y no está disponible para la venta.',
            ], 400);
        }

        $disponible = $producto->stock_disponible;

        // Unidades y su equivalencia en disponibilidad (S08)
        $unidades = $producto->unidades->map(function ($u) use ($disponible) {
            $equivalencia = (float) $u->equivalencia_base;
            return [
                'id_unidad' => $u->id_unidad,
                'nombre_unidad' => $u->nombre_unidad,
                'equivalencia_base' => $equivalencia,
                'descripcion' => $u->descripcion,
                'disponible_en_unidad' => $this->disponibleEnUnidad($disponible, $equivalencia),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'precio_unitario' => (float) $producto->precio_unitario,
                'precio_mayorista' => (float) $producto->precio_mayorista,
                'precio_minorista' => (float) $producto->precio_minorista,
                'stock_disponible' => $disponible,
                'stock_minimo' => $producto->stock_minimo,
                'estado_alerta' => $producto->estado_alerta,
                'unidades' => $unidades,
            ],
        ]);
    }

    /**
     * S12 - Historial de movimientos de un producto (ingresos, ventas, devoluciones, ajustes).
     */
    public function historial(Request $request, int $id): JsonResponse
    {
        $producto = Producto::with('movimientos.unidad')->find($id);

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
                'fecha' => $m->fecha ? $m->fecha->format('Y-m-d') : null,
                'motivo' => $m->motivo,
                'id_usuario' => $m->id_usuario,
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
            'stock_disponible' => $p->stock_disponible,
            'stock_minimo' => $p->stock_minimo,
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

    /**
     * S06 - Lotes próximos a vencer (para el dashboard "Próximos a vencer").
     * Devuelve los lotes vigentes cuyo vencimiento está dentro de
     * dias_alerta_vencimiento (30 por defecto) a partir de hoy, junto al producto.
     */
    public function lotesPorVencer(Request $request): JsonResponse
    {
        $dias = (int) $request->input('dias', 30);
        if ($dias < 1) {
            $dias = 30;
        }
        $hasta = now()->copy()->addDays($dias)->toDateString();
        $hoy = now()->toDateString();

        $lotes = DB::table('lote as l')
            ->join('producto as p', 'p.id_producto', '=', 'l.id_producto')
            ->leftJoin('unidad_medida as u', 'u.id_unidad', '=', 'l.id_unidad')
            ->where('l.estado', 'vigente')
            ->whereBetween('l.fecha_vencimiento', [$hoy, $hasta])
            ->select(
                'l.id_lote',
                'l.nro_lote',
                'l.fecha_vencimiento',
                'l.cantidad',
                'l.estado',
                'p.id_producto',
                'p.codigo',
                'p.descripcion',
                'u.id_unidad',
                'u.nombre_unidad'
            )
            ->orderBy('l.fecha_vencimiento', 'asc')
            ->get()
            ->map(function ($lote) {
                return [
                    'id_lote' => (int) $lote->id_lote,
                    'nro_lote' => (string) $lote->nro_lote,
                    'id_producto' => (int) $lote->id_producto,
                    'codigo_producto' => (string) $lote->codigo,
                    'descripcion_producto' => (string) $lote->descripcion,
                    'fecha_vencimiento' => (string) $lote->fecha_vencimiento,
                    'cantidad' => (int) $lote->cantidad,
                    'unidades' => (string) ($lote->nombre_unidad ?? ''),
                    'dias_para_vencer' => (int) now()->diffInDays($lote->fecha_vencimiento, false),
                    'estado' => (string) $lote->estado,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $lotes,
        ]);
    }
}
