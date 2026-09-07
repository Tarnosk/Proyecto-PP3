<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $query = Producto::with(['categoria', 'marca', 'stock'])
            ->where('estado', 'activo');

        if ($search && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('codigo', 'LIKE', "%{$term}%")
                  ->orWhere('descripcion', 'LIKE', "%{$term}%");
            });
        }

        if ($estado && in_array($estado, ['normal', 'bajo', 'critico'], true)) {
            $query->whereHas('stock', function ($q) use ($estado) {
                $q->where('estado_alerta', $estado);
            });
        }

        $productos = $query->orderBy('descripcion', 'asc')
            ->get()
            ->filter(fn (Producto $p) => $p->stock !== null)
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
        $producto = Producto::with(['stock', 'unidades'])->find($id);

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

        $stock = $producto->stock;
        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto no posee registro de stock.',
            ], 400);
        }

        $disponible = (int) $stock->stock_disponible;

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
                'stock_disponible' => $disponible,
                'stock_minimo' => (int) $stock->stock_minimo,
                'estado_alerta' => $stock->estado_alerta,
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
            'stock_disponible' => (int) $p->stock->stock_disponible,
            'stock_minimo' => (int) $p->stock->stock_minimo,
            'estado_alerta' => $p->stock->estado_alerta,
            'precio_unitario' => (float) $p->precio_unitario,
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
