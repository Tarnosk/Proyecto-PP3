<?php

namespace App\Services\Gateways;

use App\Contracts\SalesGateway;
use App\Services\ReportData;
use Illuminate\Support\Collection;

class HttpSalesGateway implements SalesGateway
{
    public function __construct(private ExternalJsonClient $client, private ReportData $data) {}

    public function clientHistory(int $clientId, ?string $from, ?string $to): Collection
    {
        return $this->data->sales($this->client->get('sales', '/api/v1/ventas', array_filter([
            'cliente_id' => $clientId,
            'desde' => $from,
            'hasta' => $to,
        ], fn ($value) => $value !== null)));
    }
}
