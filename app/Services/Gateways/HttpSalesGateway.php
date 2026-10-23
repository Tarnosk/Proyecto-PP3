<?php

namespace App\Services\Gateways;

use App\Contracts\SalesGateway;
use App\Modules\Reportes\Services\ReportData;
use App\Modules\Reportes\Services\SalesAnalytics;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class HttpSalesGateway implements SalesGateway
{
    public function __construct(private ExternalJsonClient $client, private ReportData $data, private SalesAnalytics $analytics) {}

    public function clientHistory(int $clientId, ?string $from, ?string $to): Collection
    {
        $sales = $this->data->sales($this->client->getList('sales', config('services.sales.history_path'), array_filter([
            'cliente_id' => $clientId,
            'desde' => $from,
            'hasta' => $to,
        ], fn ($value) => $value !== null)));

        abort_if($sales->contains(fn ($sale) => $sale->cliente->id !== $clientId
            || ($from !== null && $sale->fecha->toDateString() < $from)
            || ($to !== null && $sale->fecha->toDateString() > $to)),
            502, 'Ventas devolvio registros fuera del cliente o periodo solicitado.');

        return $sales;
    }

    public function productBuyerIds(int $productId): array
    {
        if (blank(config('services.sales.buyers_path'))) {
            return $this->analytics->buyerIds($productId);
        }
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
