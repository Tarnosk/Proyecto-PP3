<?php

declare(strict_types=1);

namespace App\Modules\Catalogo\Services;

use App\Modules\Catalogo\Data\ProductoCatalogo;
use App\Services\Gateways\ExternalJsonClient;
use Illuminate\Support\Collection;

final class CatalogoGateway
{
    public function __construct(private ExternalJsonClient $client) {}

    public function products(): Collection
    {
        // La fuente devuelve también inactivos si no se solicita el estado explícito.
        $rows = $this->client->getList('catalog', (string) config('services.catalog.products_path'), ['estado' => 'activo']);
        abort_if(count($rows) > 20000, 502, 'El catálogo supera el tamaño admitido.');
        $products = collect();
        $ids = [];
        foreach ($rows as $row) {
            abort_unless(is_array($row) && isset($row['estado']) && in_array($row['estado'], ['activo', 'inactivo'], true), 502, 'Estado de producto inválido.');
            if ($row['estado'] !== 'activo') {
                continue;
            }
            $product = ProductoCatalogo::fromApi($row);
            abort_if(isset($ids[$product->id]), 502, 'El servicio devolvió productos repetidos.');
            $ids[$product->id] = true;
            $products->push($product);
        }

        return $products;
    }

    public function find(int $id): ProductoCatalogo
    {
        abort_unless($this->client->isConfigured('catalog'), 503, 'El catálogo no está conectado.');
        $path = rtrim((string) config('services.catalog.products_path'), '/').'/'.$id;
        abort_unless(str_starts_with($path, '/') && ! str_starts_with($path, '//') && ! str_contains($path, '://'), 503);
        $response = $this->client->request('catalog', $path);
        abort_if($response->status() === 404, 404);
        abort_unless($response->successful(), 502, 'No se pudo consultar el producto.');
        $body = $response->json();
        abort_unless(is_array($body) && ($body['status'] ?? null) !== 'error' && ! ($body['error'] ?? false)
            && is_array($body['data'] ?? null) && ! array_is_list($body['data']), 502);
        $row = $body['data'];
        abort_unless(($row['estado'] ?? null) === 'activo', 404);
        $product = ProductoCatalogo::fromApi($row);
        abort_unless($product->id === $id, 502);

        return $product;
    }
}
