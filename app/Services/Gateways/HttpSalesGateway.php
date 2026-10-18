<?php

namespace App\Services\Gateways;

use App\Contracts\SalesGateway;
use App\Services\ReportData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class HttpSalesGateway implements SalesGateway
{
    public function __construct(private ExternalJsonClient $client, private ReportData $data) {}

    public function clientHistory(int $clientId, ?string $from, ?string $to): Collection
    {
        return $this->data->sales($this->client->get('sales', config('services.sales.history_path'), array_filter([
            'cliente_id' => $clientId,
            'desde' => $from,
            'hasta' => $to,
        ], fn ($value) => $value !== null)));
    }

    public function productBuyerIds(int $productId): array
    {
        $path = str_replace('{id}', (string) $productId, config('services.sales.buyers_path'));
        $rows = $this->client->get('sales', $path);
        if (! array_is_list($rows) || Validator::make($rows, [
            '*' => ['array'],
            '*.cliente_id' => ['required', 'integer', 'min:1', 'distinct'],
        ])->fails()) {
            abort(502, 'El servicio de ventas devolvio compradores invalidos.');
        }

        return array_column($rows, 'cliente_id');
    }
}
