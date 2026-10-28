<?php

namespace App\Modules\Catalogo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Data\ProductoCatalogo;
use App\Modules\Catalogo\Services\CatalogoGateway;
use App\Modules\Catalogo\Services\CatalogoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogoApiController extends Controller
{
    public function index(Request $request, CatalogoService $catalog): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'categoria' => ['nullable', 'regex:/^(?:[1-9][0-9]{0,9}|otros)$/'],
            'marca' => ['nullable', 'regex:/^(?:[1-9][0-9]{0,9}|otras)$/'],
            'disponibilidad' => ['nullable', 'in:disponible,sin-stock,consultar'],
            'orden' => ['nullable', 'in:nombre,codigo,precio-asc,precio-desc'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        $result = $catalog->browse($filters);
        $products = $result['products'];

        return response()->json([
            'data' => $products->getCollection()->map(fn ($p) => $this->product($p))->values(),
            'meta' => ['current_page' => $products->currentPage(), 'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(), 'total' => $products->total(),
                'aproximado' => $result['approximate'], 'consultado_en' => $result['checkedAt']->toIso8601String()],
            'filtros' => ['categorias' => $result['categories'], 'marcas' => $result['brands']],
        ])->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, int $producto, CatalogoGateway $gateway): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        return response()->json(['data' => $this->product($gateway->find($producto))])
            ->header('Cache-Control', 'no-store');
    }

    private function product(ProductoCatalogo $product): array
    {
        return ['id' => $product->id, 'codigo' => $product->codigo, 'nombre' => $product->nombre,
            'descripcion' => $product->descripcion,
            'categoria' => ['id' => $product->categoriaId, 'nombre' => $product->categoria],
            'marca' => ['id' => $product->marcaId, 'nombre' => $product->marca],
            'precio' => $product->precio, 'moneda' => 'ARS', 'presentacion' => $product->presentacion,
            'disponibilidad' => $product->disponibilidad, 'imagen' => $product->imagen];
    }
}
