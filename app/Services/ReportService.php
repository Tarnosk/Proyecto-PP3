<?php

namespace App\Services;

use App\Contracts\SalesGateway;
use App\Models\Cliente;
use App\Services\Gateways\ExternalJsonClient;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(private SalesGateway $sales, private ExternalJsonClient $client) {}

    public function salesAvailable(): bool
    {
        return $this->client->isConfigured('sales');
    }

    public function clientSales(Cliente $cliente, ?string $from = null, ?string $to = null): Collection
    {
        return $this->sales->clientHistory($cliente->id, $from, $to);
    }

    public function productBuyerIds(int $productId): array
    {
        return $this->sales->productBuyerIds($productId);
    }
}
