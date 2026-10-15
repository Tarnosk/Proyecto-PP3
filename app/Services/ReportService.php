<?php

namespace App\Services;

use App\Contracts\SalesGateway;
use App\Models\Cliente;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(private SalesGateway $sales) {}

    public function clientSales(Cliente $cliente, ?string $from = null, ?string $to = null): Collection
    {
        return $this->sales->clientHistory($cliente->id, $from, $to);
    }
}
