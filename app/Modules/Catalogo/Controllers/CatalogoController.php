<?php

declare(strict_types=1);

namespace App\Modules\Catalogo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Services\CatalogoGateway;
use App\Modules\Catalogo\Services\CatalogoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class CatalogoController extends Controller
{
    public function index(Request $request, CatalogoService $catalog)
    {
        [$data, $status] = $this->data($request, $catalog);

        return (new Response(view('catalogo.index', $data), $status))->header('Cache-Control', 'no-store');
    }

    public function dataJson(Request $request, CatalogoService $catalog)
    {
        [$data, $status] = $this->data($request, $catalog);
        if ($status !== 200) {
            return (new JsonResponse(['message' => 'No pudimos actualizar el catálogo. Volvé a intentarlo.'], $status))->header('Cache-Control', 'no-store');
        }

        return (new JsonResponse([
            'html' => view('catalogo.partials.results', $data)->render(),
            'categories' => $data['categories'], 'brands' => $data['brands'],
            'checked_at' => $data['checkedAt']->format('H:i'),
        ]))->header('Cache-Control', 'no-store');
    }

    public function show(int $producto, CatalogoGateway $gateway)
    {
        try {
            $product = $gateway->find($producto);
            $related = collect();
            try {
                $related = $gateway->products()->filter(fn ($p) => $p->id !== $product->id && $p->categoriaId === $product->categoriaId
                    && in_array($p->disponibilidad, ['disponible', 'bajo'], true))->take(3);
            } catch (HttpExceptionInterface) {
                // El detalle sigue siendo válido aunque la consulta opcional de alternativas falle.
            }

            return (new Response(view('catalogo.show', ['product' => $product, 'related' => $related,
                'checkedAt' => now()->timezone('America/Argentina/Buenos_Aires')])))->header('Cache-Control', 'no-store');
        } catch (HttpExceptionInterface $error) {
            return (new Response(view('catalogo.unavailable', ['notFound' => $error->getStatusCode() === 404]), $error->getStatusCode()))->header('Cache-Control', 'no-store');
        }
    }

    private function data(Request $request, CatalogoService $catalog): array
    {
        // Las rutas públicas no dependen de sesión ni de tablas de usuarios.
        $filters = array_intersect_key($request->query(), array_flip(['q', 'categoria', 'marca', 'disponibilidad', 'orden', 'page']));
        $validator = validator($filters, [
            'q' => ['nullable', 'string', 'max:120'], 'categoria' => ['nullable', 'regex:/^(?:[1-9][0-9]{0,9}|otros)$/'],
            'marca' => ['nullable', 'regex:/^(?:[1-9][0-9]{0,9}|otras)$/'],
            'disponibilidad' => ['nullable', 'in:disponible,sin-stock,consultar'],
            'orden' => ['nullable', 'in:nombre,codigo,precio-asc,precio-desc'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);
        if ($validator->fails()) {
            return [$this->emptyData([], 'La búsqueda no es válida. Revisá los filtros e intentá de nuevo.'), 422];
        }
        $filters = array_filter($validator->validated(), fn ($value) => $value !== null && $value !== '');
        try {
            return [$catalog->browse($filters), 200];
        } catch (HttpExceptionInterface $error) {
            return [$this->emptyData($filters), in_array($error->getStatusCode(), [502, 503], true) ? $error->getStatusCode() : 503];
        }
    }

    private function emptyData(array $filters, ?string $message = null): array
    {
        return ['products' => null, 'categories' => [], 'brands' => [], 'filters' => $filters,
            'approximate' => false, 'totalCatalog' => null, 'availableCount' => null, 'checkedAt' => null,
            'unavailable' => true, 'message' => $message];
    }
}
