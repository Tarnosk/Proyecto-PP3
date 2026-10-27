<?php

declare(strict_types=1);

namespace App\Modules\Catalogo\Services;

use App\Modules\Catalogo\Data\ProductoCatalogo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class CatalogoService
{
    public function __construct(private CatalogoGateway $gateway) {}

    public function browse(array $filters): array
    {
        $all = $this->gateway->products();
        $query = ProductoCatalogo::normalize($filters['q'] ?? '');
        $matched = $all->filter(fn ($p) => $this->matches($p, $query));
        $approximate = false;
        if ($query !== '' && $matched->isEmpty()) {
            $matched = $all->filter(fn ($p) => $this->matches($p, $query, true));
            $approximate = $matched->isNotEmpty();
        }
        $categories = $this->facets($this->filter($matched, $filters, 'categoria'), 'categoriaId', 'categoria');
        $brands = $this->facets($this->filter($matched, $filters, 'marca'), 'marcaId', 'marca');
        $filtered = $this->filter($matched, $filters);
        $sort = $filters['orden'] ?? 'nombre';
        $filtered = match ($sort) {
            'precio-asc' => $filtered->sortBy(fn ($p) => $p->precio === null ? INF : (float) $p->precio),
            'precio-desc' => $filtered->sortBy(fn ($p) => $p->precio === null ? INF : -(float) $p->precio),
            'codigo' => $filtered->sortBy('codigo', SORT_NATURAL | SORT_FLAG_CASE),
            default => $filtered->sortBy(fn ($p) => ProductoCatalogo::normalize($p->nombre)),
        };
        $filtered = $filtered->values();
        $perPage = 12;
        $page = min((int) ($filters['page'] ?? 1), max(1, (int) ceil($filtered->count() / $perPage)));
        $paginator = new LengthAwarePaginator($filtered->forPage($page, $perPage)->values(), $filtered->count(), $perPage, $page, [
            'path' => route('catalogo.index'), 'query' => $filters,
        ]);

        return [
            'products' => $paginator, 'categories' => $categories, 'brands' => $brands,
            'filters' => $filters, 'approximate' => $approximate, 'totalCatalog' => $all->count(),
            'checkedAt' => now()->timezone('America/Argentina/Buenos_Aires'), 'unavailable' => false,
            'availableCount' => $all->whereIn('disponibilidad', ['disponible', 'bajo'])->count(),
        ];
    }

    private function filter(Collection $products, array $filters, ?string $except = null): Collection
    {
        return $products->filter(function ($p) use ($filters, $except) {
            if ($except !== 'categoria' && filled($filters['categoria'] ?? null) && $p->categoriaId !== $filters['categoria']) {
                return false;
            }
            if ($except !== 'marca' && filled($filters['marca'] ?? null) && $p->marcaId !== $filters['marca']) {
                return false;
            }

            return match ($filters['disponibilidad'] ?? '') {
                'disponible' => in_array($p->disponibilidad, ['disponible', 'bajo'], true),
                'sin-stock' => $p->disponibilidad === 'sin-stock',
                'consultar' => $p->disponibilidad === 'consultar',
                default => true,
            };
        });
    }

    private function facets(Collection $products, string $id, string $label): array
    {
        return $products->groupBy($id)->map(fn ($rows, $key) => [
            'id' => (string) $key, 'nombre' => $rows->first()->{$label}, 'cantidad' => $rows->count(),
        ])->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }

    private function matches(ProductoCatalogo $product, string $query, bool $fuzzy = false): bool
    {
        $text = $product->searchText();
        foreach (array_filter(explode(' ', $query)) as $token) {
            if (str_contains($text, $token)) {
                continue;
            }
            if (! $fuzzy || strlen($token) < 4 || ! ctype_alpha($token)) {
                return false;
            }
            $similar = false;
            foreach (explode(' ', $text) as $word) {
                if (levenshtein($token, $word) <= (strlen($token) > 7 ? 2 : 1)) {
                    $similar = true;
                    break;
                }
            }
            if (! $similar) {
                return false;
            }
        }

        return true;
    }
}
