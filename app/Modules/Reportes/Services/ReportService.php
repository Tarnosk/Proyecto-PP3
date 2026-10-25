<?php

namespace App\Modules\Reportes\Services;

use App\Contracts\SalesGateway;
use App\Modules\Clientes\Models\Cliente;
use App\Services\Gateways\ExternalJsonClient;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(private SalesGateway $sales, private ExternalJsonClient $client, private SalesAnalytics $analytics) {}

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

    public function salesProductReach(?int $productId): array
    {
        return $this->analytics->productReach($productId);
    }

    public function buyersAvailable(): bool
    {
        return $this->salesAvailable() && (filled(config('services.sales.buyers_path')) || filled(config('services.sales.history_path')));
    }
}
