<?php

namespace App\Modules\Reportes\Services;

use App\Modules\Reportes\Enums\SaleStatus;
use App\Services\Gateways\ExternalJsonClient;
use Illuminate\Support\Facades\Validator;

/** Reportes de G1 derivados exclusivamente de la API de lectura de Ventas. */
class SalesAnalytics
{
    public function __construct(private ExternalJsonClient $client, private ReportData $data) {}

    public function buyerIds(int $productId): array
    {
        $ids = [];
        foreach ($this->sales(null, null) as $sale) {
            foreach ($sale['detalles'] as $detail) {
                if ((int) $detail['id_producto'] === $productId) {
                    $ids[(int) $sale['cliente']['id']] = true;
                }
            }
        }
        $ids = array_keys($ids);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    /** Productos vendidos y compradores calculados sobre la misma lectura completa. */
    public function productReach(?int $productId): array
    {
        $products = [];
        $buyers = [];
        foreach ($this->sales(null, null) as $sale) {
            foreach ($sale['detalles'] as $detail) {
                $id = (int) $detail['id_producto'];
                $products[$id] ??= (object) ['id' => $id, 'nombre' => $detail['producto'],
                    'codigo' => is_string($detail['codigo'] ?? null) ? $detail['codigo'] : null];
                if ($id === $productId) {
                    $buyers[(int) $sale['cliente']['id']] = true;
                }
            }
        }

        return ['products' => collect($products)->sortBy('nombre')->values(), 'buyer_ids' => array_keys($buyers)];
    }

    public function ranking(?string $from, ?string $to): array
    {
        $products = [];
        foreach ($this->sales($from, $to) as $sale) {
            // subtotal ya contiene el descuento de cada linea en el contrato G4.
            $weights = array_map(fn ($detail) => $this->cents($detail['subtotal']), $sale['detalles']);
            $gross = 0;
            foreach ($weights as $weight) {
                abort_if($weight > intdiv(PHP_INT_MAX, 2) - $gross, 502, 'El subtotal de Ventas excede el rango admitido.');
                $gross += $weight;
            }
            $net = $this->cents($sale['total']);
            abort_unless(array_key_exists('descuento_global', $sale), 502, 'Ventas no informo el descuento global.');
            $discount = $this->cents($sale['descuento_global']);
            abort_unless($gross - $discount === $net, 502, 'El total de Ventas no coincide con sus detalles y descuento global.');

            $allocations = [];
            $remaining = $net;
            foreach ($weights as $index => $weight) {
                [$amount, $remainder] = $gross === 0 ? [0, 0] : $this->proportion($net, $weight, $gross);
                $allocations[$index] = ['amount' => $amount, 'remainder' => $remainder,
                    'product' => (int) $sale['detalles'][$index]['id_producto'], 'index' => $index];
                $remaining -= $amount;
            }
            // Mayor resto: cada centavo se asigna una sola vez, con desempate estable.
            uasort($allocations, fn ($a, $b) => ($b['remainder'] <=> $a['remainder'])
                ?: ($a['product'] <=> $b['product']) ?: ($a['index'] <=> $b['index']));
            foreach ($allocations as &$allocation) {
                if ($remaining > 0) {
                    $allocation['amount']++;
                    $remaining--;
                }
            }
            unset($allocation);
            foreach ($sale['detalles'] as $index => $detail) {
                $id = (int) $detail['id_producto'];
                $products[$id] ??= ['producto_id' => $id, 'producto_nombre' => $detail['producto'],
                    'cantidad_vendida' => 0, 'net_cents' => 0];
                abort_if($products[$id]['cantidad_vendida'] > PHP_INT_MAX - (int) $detail['cantidad']
                    || $products[$id]['net_cents'] > PHP_INT_MAX - $allocations[$index]['amount'],
                    502, 'El ranking de Ventas excede el rango admitido.');
                $products[$id]['cantidad_vendida'] += (int) $detail['cantidad'];
                $products[$id]['net_cents'] += $allocations[$index]['amount'];
            }
        }
        usort($products, fn ($a, $b) => ($b['cantidad_vendida'] <=> $a['cantidad_vendida']) ?: ($a['producto_id'] <=> $b['producto_id']));

        return array_map(function ($product) {
            $cents = $product['net_cents'];
            unset($product['net_cents']);
            // Se conserva la clave del DTO de G1; representa importe neto vendido.
            $product['monto_facturado'] = intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);

            return $product;
        }, $products);
    }

    private function sales(?string $from, ?string $to): array
    {
        $rows = $this->client->getList('sales', config('services.sales.history_path'), array_filter([
            'desde' => $from, 'hasta' => $to,
        ], fn ($value) => $value !== null));
        $validated = $this->data->sales($rows);
        abort_if($validated->contains(fn ($sale) => ($from !== null && $sale->fecha->toDateString() < $from)
            || ($to !== null && $sale->fecha->toDateString() > $to)),
            502, 'Ventas devolvio registros fuera del periodo solicitado.');
        abort_if(Validator::make($rows, ['*.anulada' => ['sometimes', 'boolean']])->fails(), 502, 'Ventas devolvio una anulacion invalida.');

        $eligible = array_values(array_filter($rows, fn ($sale) => ! ($sale['anulada'] ?? false)
            && in_array(SaleStatus::from($sale['estado']['valor']), [SaleStatus::Confirmada, SaleStatus::Preparada,
                SaleStatus::Entregada, SaleStatus::Pagada, SaleStatus::Facturada], true)));
        abort_if(Validator::make($eligible, [
            '*.detalles' => ['required', 'array', 'min:1'],
            '*.detalles.*.id_producto' => ['required', 'integer', 'min:1'],
            '*.detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            '*.detalles.*.subtotal' => ['required', 'numeric', 'min:0'],
        ])->fails(), 502, 'Ventas devolvio detalles incompletos para el reporte.');
        abort_if(collect($eligible)->contains(fn ($sale) => ! array_is_list($sale['detalles'])),
            502, 'Ventas devolvio una lista de detalles invalida.');

        return $eligible;
    }

    private function cents(mixed $value): int
    {
        abort_unless(is_int($value) || is_float($value) || is_string($value),
            502, 'Ventas devolvio un importe monetario invalido.');
        // El cast directo de float usa precision de pantalla y puede redondear importes grandes.
        $decimal = is_float($value) ? (string) json_encode($value, JSON_PRESERVE_ZERO_FRACTION) : (string) $value;
        abort_unless(preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $decimal),
            502, 'Ventas devolvio un importe monetario invalido.');
        $parts = explode('.', $decimal);

        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    /** Division entera de amount * weight / divisor sin desbordar al multiplicar. */
    private function proportion(int $amount, int $weight, int $divisor): array
    {
        $quotient = $remainder = 0;
        $partQuotient = intdiv($amount, $divisor);
        $partRemainder = $amount % $divisor;
        while ($weight > 0) {
            if ($weight % 2 === 1) {
                $quotient += $partQuotient;
                $remainder += $partRemainder;
                $quotient += intdiv($remainder, $divisor);
                $remainder %= $divisor;
            }
            $weight = intdiv($weight, 2);
            if ($weight > 0) {
                $partQuotient *= 2;
                $partRemainder *= 2;
                $partQuotient += intdiv($partRemainder, $divisor);
                $partRemainder %= $divisor;
            }
        }

        return [$quotient, $remainder];
    }
}
