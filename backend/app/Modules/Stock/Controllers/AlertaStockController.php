<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlertaStockController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S07 - Listar productos que requieren atención según su estado de alerta.
     * Filtra por 'bajo' o 'critico' (por defecto ambos).
     */
    public function index(Request $request): JsonResponse
    {
        $filtro = $request->input('nivel'); // bajo | critico | nada

        $query = Producto::with(['stock', 'categoria', 'marca'])
            ->where('estado', 'activo')
            ->whereHas('stock');

        if ($filtro && in_array($filtro, ['bajo', 'critico'], true)) {
            $query->whereHas('stock', function ($q) use ($filtro) {
                $q->where('estado_alerta', $filtro);
            });
        } else {
            // Ambos estados de alerta (bajo y crítico)
            $query->whereHas('stock', function ($q) {
                $q->whereIn('estado_alerta', ['bajo', 'critico']);
            });
        }

        $alertas = $query->orderBy('descripcion', 'asc')
            ->get()
            ->filter(fn (Producto $p) => $p->stock !== null)
            ->values()
            ->map(function (Producto $p) {
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
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $alertas,
        ]);
    }

    /**
     * S07 - Configurar el stock mínimo de un producto y recalcular su alerta.
     */
    public function configurarMinimo(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stock_minimo' => 'required|integer|min:0',
        ], [
            'stock_minimo.required' => 'Debe indicar un stock mínimo.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $producto = Producto::with('stock')->find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        if (!$producto->stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto no posee registro de stock.',
            ], 400);
        }

        $producto->stock->stock_minimo = (int) $request->input('stock_minimo');
        $producto->stock->save();

        $this->stockService->recalcularAlerta($producto->stock);
        $producto->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock mínimo configurado exitosamente.',
            'data' => [
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'stock_disponible' => (int) $producto->stock->stock_disponible,
                'stock_minimo' => (int) $producto->stock->stock_minimo,
                'estado_alerta' => $producto->stock->estado_alerta,
            ],
        ]);
    }

    /**
     * S07 - Recalcular todos los estados de alerta del stock (útil tras cargas masivas).
     */
    public function recalcular(Request $request): JsonResponse
    {
        $contador = 0;
        Stock::chunk(200, function ($stocks) use (&$contador) {
            foreach ($stocks as $stock) {
                $this->stockService->recalcularAlerta($stock);
                $contador++;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => "Alertas recalculadas para {$contador} producto(s).",
            'data' => ['procesados' => $contador],
        ]);
    }
}
