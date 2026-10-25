<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
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
     * El estado de alerta es derivado (Producto->estado_alerta); filtra por
     * 'bajo' o 'critico' (por defecto ambos).
     */
    public function index(Request $request): JsonResponse
    {
        $filtro = $request->input('nivel'); // bajo | critico | nada

        $productos = Producto::with(['categoria', 'marca'])
            ->where('estado', 'activo')
            ->orderBy('descripcion', 'asc')
            ->get()
            ->filter(function (Producto $p) use ($filtro) {
                if ($filtro && in_array($filtro, ['bajo', 'critico'], true)) {
                    return $p->estado_alerta === $filtro;
                }
                return in_array($p->estado_alerta, ['bajo', 'critico'], true);
            })
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
                    'stock_disponible' => $p->stock_disponible,
                    'stock_minimo' => $p->stock_minimo,
                    'estado_alerta' => $p->estado_alerta,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $productos,
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

        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        $producto->stock_minimo = (int) $request->input('stock_minimo');
        $producto->save();

        $this->stockService->recalcularAlerta($producto);
        $producto->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock mínimo configurado exitosamente.',
            'data' => [
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'stock_disponible' => $producto->stock_disponible,
                'stock_minimo' => $producto->stock_minimo,
                'estado_alerta' => $producto->estado_alerta,
            ],
        ]);
    }

    /**
     * S07 - Recalcular todos los estados de alerta del stock (útil tras cargas masivas).
     * El estado es derivado, por lo que basta con recorrer los productos activos.
     */
    public function recalcular(Request $request): JsonResponse
    {
        $contador = 0;
        Producto::chunk(200, function ($productos) use (&$contador) {
            foreach ($productos as $producto) {
                $this->stockService->recalcularAlerta($producto);
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
