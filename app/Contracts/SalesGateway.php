<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface SalesGateway
{
    public function clientHistory(int $clientId, ?string $from, ?string $to): Collection;

    public function productBuyerIds(int $productId): array;
}
