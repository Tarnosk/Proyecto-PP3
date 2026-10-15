<?php

namespace App\Services\Gateways;

use App\Contracts\SalesGateway;
use App\Models\Venta;
use App\Services\ReportData;
use Illuminate\Support\Collection;

class LocalSalesGateway implements SalesGateway
{
    public function __construct(private ReportData $data) {}

    public function clientHistory(int $clientId, ?string $from, ?string $to): Collection
    {
        $sales = Venta::query()
            ->with(['cliente:id,nombre,apellido_razon_social', 'detalles.producto:id,nombre'])
            ->where('cliente_id', $clientId)
            ->when($from, fn ($query) => $query->whereDate('fecha', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('fecha', '<=', $to))
            ->orderByDesc('fecha')->orderByDesc('id')->get();

        return $this->data->sales($sales->map(fn (Venta $sale): array => $this->saleData($sale))->all());
    }

    private function saleData(Venta $sale): array
    {
        return [
            'fecha' => $sale->fecha->toIso8601String(),
            'numero_factura' => $sale->numero_factura,
            'total' => $sale->total,
            'estado' => $sale->estado->value,
            'cliente' => [
                'nombre' => $sale->cliente->nombre,
                'apellido_razon_social' => $sale->cliente->apellido_razon_social,
            ],
            'detalles' => $sale->relationLoaded('detalles')
                ? $sale->detalles->map(fn ($detail): array => [
                    'cantidad' => $detail->cantidad,
                    'subtotal' => $detail->subtotal,
                    'producto' => ['nombre' => $detail->producto->nombre],
                ])->all()
                : [],
        ];
    }
}
