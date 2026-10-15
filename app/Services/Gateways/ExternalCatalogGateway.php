<?php

namespace App\Services\Gateways;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class ExternalCatalogGateway
{
    public function __construct(private ExternalJsonClient $client) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured('catalog');
    }

    public function products(): Collection
    {
        $rows = $this->client->get('catalog', config('services.catalog.products_path'));
        if (! array_is_list($rows) || Validator::make($rows, [
            '*' => ['array'],
            '*.id_producto' => ['required', 'integer', 'min:1', 'distinct'],
            '*.codigo' => ['required', 'string'],
            '*.descripcion' => ['required', 'string'],
            '*.nombre' => ['nullable', 'string'],
        ])->fails()) {
            abort(502, 'El catalogo de productos devolvio datos invalidos.');
        }

        return collect($rows)->map(fn (array $row): object => (object) [
            'id' => (int) $row['id_producto'],
            'codigo' => $row['codigo'],
            'nombre' => ($row['nombre'] ?? null) ?: $row['descripcion'],
        ])->sortBy('nombre')->values();
    }
}
