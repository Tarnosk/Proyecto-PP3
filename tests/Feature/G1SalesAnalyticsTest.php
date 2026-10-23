<?php

namespace Tests\Feature;

use App\Contracts\SalesGateway;
use App\Modules\Reportes\Services\SalesAnalytics;
use App\Services\Gateways\ExternalFinanceGateway;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Sin esquemas ni datos de otros grupos: solo respuestas HTTP del contrato publicado. */
class G1SalesAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sales.url' => 'https://g4.test', 'services.sales.token' => null,
            'services.sales.auth_required' => false, 'services.sales.history_path' => '/api/ventas',
            'services.sales.buyers_path' => null, 'services.sales.ranking_path' => null]);
        Http::preventStrayRequests();
    }

    private function detail(int $id = 42, int $quantity = 2, mixed $subtotal = '20.00'): array
    {
        return ['id_producto' => $id, 'codigo' => 'P-'.$id, 'producto' => 'Producto '.$id,
            'cantidad' => $quantity, 'subtotal' => $subtotal, 'precio_unitario' => 15, 'descuento' => 5];
    }

    private function sale(int $id = 1, int $client = 12, string $status = 'confirmada', ?array $details = null,
        mixed $total = '20.00', mixed $discount = '0.00'): array
    {
        return ['id' => $id, 'fecha' => '2026-10-22', 'numero_comprobante' => 'V-'.$id,
            'cliente' => ['id' => $client, 'nombre' => 'Cliente '.$client],
            'estado' => ['valor' => $status], 'anulada' => false,
            'detalles' => $details ?? [$this->detail()], 'total' => $total, 'descuento_global' => $discount];
    }

    private function respond(array $rows): void
    {
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['g4.test/api/ventas*' => Http::response(['data' => $rows])]);
    }

    private function error(int $status, callable $operation): void
    {
        try {
            $operation();
            $this->fail('Se esperaba rechazo de la respuesta externa.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    public function test_only_confirmed_and_later_sales_count_for_buyers_and_net_ranking(): void
    {
        $states = ['pendiente', 'confirmada', 'preparada', 'entregada', 'pagada', 'facturada', 'cancelada'];
        $sales = array_map(fn ($state, $index) => $this->sale($index + 1, $index + 10, $state), $states, array_keys($states));
        $annulled = $this->sale(8, 99);
        $annulled['anulada'] = true;
        $sales[] = $annulled;
        $this->respond($sales);
        DB::enableQueryLog();
        $this->assertSame([11, 12, 13, 14, 15], app(SalesGateway::class)->productBuyerIds(42));
        $this->assertSame([['producto_id' => 42, 'producto_nombre' => 'Producto 42', 'cantidad_vendida' => 10,
            'monto_facturado' => '100.00']], app(ExternalFinanceGateway::class)->productRanking(null, null));
        $this->assertSame([], DB::getQueryLog());
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->method() === 'GET' && ! isset($request['cliente_id'])
            && ! $request->hasHeader('Cookie') && ! $request->hasHeader('X-User-Id'));
    }

    public function test_buyers_are_deduplicated_across_lines_sales_and_all_pages(): void
    {
        Http::fake(function ($request) {
            $page = (int) $request['page'];
            $rows = $page === 1 ? [$this->sale(1, 12), $this->sale(2, 12)] : [
                $this->sale(3, 9, 'entregada', [$this->detail(), $this->detail()], '40.00'),
                $this->sale(4, 88, 'confirmada', [$this->detail(7)]),
            ];

            return Http::response(['data' => $rows, 'meta' => ['current_page' => $page, 'last_page' => 2, 'total' => 4]]);
        });
        $this->assertSame([9, 12], app(SalesGateway::class)->productBuyerIds(42));
        Http::assertSentCount(2);
    }

    public function test_ranking_counts_quantities_and_net_money_without_applying_line_discounts_twice(): void
    {
        $this->respond([
            $this->sale(1, 12, 'confirmada', [$this->detail(42, 2, '20.00'), $this->detail(7, 1, '10.00')], '27.00', '3.00'),
            $this->sale(2, 12, 'pagada', [$this->detail(42, 1, '10.00'), $this->detail(42, 1, '10.00')], '20.00'),
        ]);
        $ranking = app(SalesAnalytics::class)->ranking(null, null);
        $this->assertSame([42, 7], array_column($ranking, 'producto_id'));
        $this->assertSame([4, 1], array_column($ranking, 'cantidad_vendida'));
        $this->assertSame(['38.00', '9.00'], array_column($ranking, 'monto_facturado'));
    }

    public function test_rounding_allocates_every_cent_deterministically_and_keeps_free_lines_zero(): void
    {
        $this->respond([$this->sale(1, 12, 'confirmada', [
            $this->detail(3, 1, '0.01'), $this->detail(2, 1, '0.01'), $this->detail(1, 1, '0.01'),
            $this->detail(4, 1, '0.00'),
        ], '0.02', '0.01')]);
        $ranking = app(SalesAnalytics::class)->ranking(null, null);
        $this->assertSame([1, 2, 3, 4], array_column($ranking, 'producto_id'));
        $this->assertSame(['0.01', '0.01', '0.00', '0.00'], array_column($ranking, 'monto_facturado'));
        $this->respond([$this->sale(1, 12, 'confirmada', [$this->detail(1, 1, 0)], 0)]);
        $this->assertSame('0.00', app(SalesAnalytics::class)->ranking(null, null)[0]['monto_facturado']);
    }

    public function test_large_amounts_are_prorated_in_cents_without_multiplication_overflow(): void
    {
        $this->respond([$this->sale(1, 12, 'confirmada', [
            $this->detail(2, 1, 500000000000.00), $this->detail(1, 1, 499999999999.99),
        ], 999999999999.98, 0.01)]);
        $this->assertSame(['499999999999.99', '499999999999.99'],
            array_column(app(SalesAnalytics::class)->ranking(null, null), 'monto_facturado'));
    }

    public function test_missing_product_ids_and_invalid_quantities_are_rejected_instead_of_guessing(): void
    {
        $withoutId = $this->sale();
        unset($withoutId['detalles'][0]['id_producto']);
        $zeroQuantity = $this->sale(details: [$this->detail(quantity: 0)]);
        foreach ([$withoutId, $zeroQuantity, $this->sale(details: [])] as $sale) {
            $this->respond([$sale]);
            $this->error(502, fn () => app(SalesGateway::class)->productBuyerIds(42));
            $this->error(502, fn () => app(SalesAnalytics::class)->ranking(null, null));
        }
    }

    public function test_inconsistent_totals_missing_discounts_and_invalid_money_are_rejected(): void
    {
        $withoutDiscount = $this->sale();
        unset($withoutDiscount['descuento_global']);
        foreach ([$withoutDiscount, $this->sale(total: '19.99'), $this->sale(discount: '-1.00'),
            $this->sale(details: [$this->detail(subtotal: '20.001')]), $this->sale(total: '20.001'),
            $this->sale(discount: null), $this->sale(discount: '21.00'),
        ] as $sale) {
            $this->respond([$sale]);
            $this->error(502, fn () => app(SalesAnalytics::class)->ranking(null, null));
        }
    }

    public function test_dates_are_forwarded_and_out_of_range_sales_are_rejected(): void
    {
        $this->respond([$this->sale()]);
        $this->assertCount(1, app(SalesAnalytics::class)->ranking('2026-10-22', '2026-10-22'));
        Http::assertSent(fn ($request) => $request['desde'] === '2026-10-22' && $request['hasta'] === '2026-10-22');
        $this->error(502, fn () => app(SalesAnalytics::class)->ranking('2026-10-23', null));
        $this->error(502, fn () => app(SalesAnalytics::class)->ranking(null, '2026-10-21'));
    }

    public function test_empty_data_is_valid_but_incomplete_pages_and_upstream_failure_are_not_empty_reports(): void
    {
        $this->respond([]);
        $this->assertSame([], app(SalesGateway::class)->productBuyerIds(42));
        $this->assertSame([], app(SalesAnalytics::class)->ranking(null, null));
        Http::swap((new Factory)->preventStrayRequests());
        Http::fakeSequence()->push(['data' => [$this->sale()], 'meta' => ['current_page' => 1, 'last_page' => 2, 'total' => 2]])
            ->push(['data' => []]);
        $this->error(502, fn () => app(SalesAnalytics::class)->ranking(null, null));
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['g4.test/*' => Http::response(['message' => 'Error'], 500)]);
        $this->error(502, fn () => app(SalesGateway::class)->productBuyerIds(42));
        $this->error(502, fn () => app(SalesAnalytics::class)->ranking(null, null));
    }

    public function test_product_selector_and_buyers_share_the_same_sales_snapshot_and_deduplicate_ids(): void
    {
        $this->respond([
            $this->sale(1, 12, 'confirmada', [$this->detail(42), $this->detail(7)], '40.00'),
            $this->sale(2, 12), $this->sale(3, 99, 'cancelada', [$this->detail(99)]),
        ]);
        $reach = app(SalesAnalytics::class)->productReach(42);
        $this->assertSame([42, 7], $reach['products']->pluck('id')->all());
        $this->assertSame('P-42', $reach['products']->first()->codigo);
        $this->assertSame([12], $reach['buyer_ids']);
        Http::assertSentCount(1);
    }
}
