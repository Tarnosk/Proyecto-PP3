<?php

namespace Tests\Feature;

use App\Contracts\SalesGateway;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use App\Modules\Reportes\Enums\SaleStatus;
use App\Services\Gateways\ExternalCatalogGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Sólo tablas de G1 en SQLite :memory:. Los otros grupos se consumen por HTTP. */
class G1PartnerApiConsumptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.sales.url' => 'https://g4.test', 'services.sales.token' => null,
            'services.sales.auth_required' => false, 'services.sales.history_path' => '/api/ventas',
            'services.sales.buyers_path' => null, 'services.sales.ranking_path' => null,
            'services.catalog.url' => null, 'services.catalog.token' => null,
            'services.billing.url' => null, 'services.balances.url' => null]);
    }

    private function fake($callback = null): void
    {
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake($callback);
    }

    private function actorAndClients(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            foreach (['nombre', 'apellido_razon_social', 'dni_cuit', 'direccion', 'email', 'telefono', 'localidad', 'estado', 'tipo_cliente'] as $column) {
                $table->string($column)->nullable();
            }
        });
        DB::table('clientes')->insert(['id' => 12, 'nombre' => 'Ana', 'apellido_razon_social' => 'Comercial Norte',
            'dni_cuit' => '30123456789', 'direccion' => 'San Martín 100', 'estado' => 'activo', 'tipo_cliente' => 'minorista']);
        $token = 'g1-partner-consumption-test';
        $user = new User;
        $user->forceFill(['id' => 27, 'nombre' => 'Administración G1', 'usuario' => 'g1-api-test',
            'rol' => UserRole::ADMINISTRATIVO, 'estado' => UserStatus::ACTIVO, 'current_session_id' => hash('sha256', $token)]);
        $this->actingAs($user)->withSession(['session_token' => $token, 'last_activity' => now()->timestamp]);
    }

    private function sale(int $id = 80, int $clientId = 12, string $status = 'preparada'): array
    {
        return ['id' => $id, 'fecha' => '2026-10-22', 'numero_comprobante' => 'POS-'.$id, 'total' => 3200, 'descuento_global' => 0,
            'cliente' => ['id' => $clientId, 'nombre' => 'Comercial Norte', 'cuit' => '30123456789'],
            'estado' => ['valor' => $status, 'etiqueta' => ucfirst($status)],
            'detalles' => [['id_producto' => 42, 'producto' => 'Yerba 1kg', 'cantidad' => 2, 'subtotal' => 3200]]];
    }

    private function assertGatewayError(int $status, callable $operation): void
    {
        try {
            $operation();
            $this->fail('La respuesta externa debía rechazarse.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    public function test_history_consumes_the_published_g4_resource_and_renders_in_g1(): void
    {
        $this->actorAndClients();
        $this->fake(['g4.test/api/ventas*' => Http::response(['data' => [$this->sale()]])]);
        $this->get('/reportes/historial-clientes?cliente_id=12&desde=2026-10-01&hasta=2026-10-22')
            ->assertOk()->assertSeeText('POS-80')->assertSeeText('Yerba 1kg')->assertSeeText('Preparada')->assertSeeText('1 operaciones');
        $sales = app(SalesGateway::class)->clientHistory(12, null, null);
        $this->assertSame(SaleStatus::Preparada, $sales->first()->estado);
        $this->assertSame('Comercial Norte', $sales->first()->cliente->nombre);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://g4.test/api/ventas?')
            && $request['cliente_id'] === 12 && ($request['desde'] ?? null) === '2026-10-01'
            && ($request['hasta'] ?? null) === '2026-10-22' && ! $request->hasHeader('Authorization'));
        $this->assertFalse(Schema::hasTable('venta'));
        $this->assertFalse(Schema::hasTable('PRODUCTO'));
    }

    public function test_history_reads_every_page_and_ignores_external_pagination_urls(): void
    {
        config(['services.sales.token' => 'g4-token']);
        $this->fake(function ($request) {
            $page = (int) $request['page'];

            return Http::response(['data' => [$this->sale($page, 12, $page === 1 ? 'preparada' : 'entregada')],
                'meta' => ['current_page' => $page, 'last_page' => 2, 'total' => 2],
                'links' => ['next' => 'https://otro.test/robar-token']]);
        });
        $sales = app(SalesGateway::class)->clientHistory(12, '2026-10-01', '2026-10-22');
        $this->assertSame([1, 2], $sales->pluck('id')->all());
        $this->assertSame(SaleStatus::Entregada, $sales->last()->estado);
        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => ! str_starts_with($request->url(), 'https://g4.test/api/ventas?'));
        Http::assertSent(fn ($request) => $request['page'] === 2 && $request['cliente_id'] === 12
            && ($request['desde'] ?? null) === '2026-10-01' && $request->hasHeader('Authorization', 'Bearer g4-token'));
    }

    public function test_history_rejects_another_client_or_dates_outside_the_requested_period(): void
    {
        $this->fake(['g4.test/*' => Http::response(['data' => [$this->sale(80, 99)]])]);
        $this->assertGatewayError(502, fn () => app(SalesGateway::class)->clientHistory(12, null, null));
        $this->fake(['g4.test/*' => Http::response(['data' => [$this->sale()]])]);
        $this->assertGatewayError(502, fn () => app(SalesGateway::class)->clientHistory(12, '2026-10-23', null));
        $this->assertGatewayError(502, fn () => app(SalesGateway::class)->clientHistory(12, null, '2026-10-21'));
    }

    public function test_missing_fields_unknown_states_and_repeated_sales_are_rejected(): void
    {
        $withoutClient = $this->sale();
        unset($withoutClient['cliente']['id']);
        foreach ([[$withoutClient], [$this->sale(80, 12, 'inexistente')], [$this->sale(), $this->sale()]] as $rows) {
            $this->fake(['g4.test/*' => Http::response(['data' => $rows])]);
            $this->assertGatewayError(502, fn () => app(SalesGateway::class)->clientHistory(12, null, null));
        }
    }

    public function test_broken_pagination_never_returns_a_partial_history(): void
    {
        foreach ([
            ['current_page' => 2, 'last_page' => 2, 'total' => 2],
            ['current_page' => 1, 'last_page' => 101, 'total' => 101],
            ['current_page' => 1, 'last_page' => 1, 'total' => 2],
        ] as $meta) {
            $this->fake(['g4.test/*' => Http::response(['data' => [$this->sale()], 'meta' => $meta])]);
            $this->assertGatewayError(502, fn () => app(SalesGateway::class)->clientHistory(12, null, null));
        }
        Http::swap((new Factory)->preventStrayRequests());
        Http::fakeSequence()->push(['data' => [$this->sale()], 'meta' => ['current_page' => 1, 'last_page' => 2, 'total' => 2]])
            ->push(['data' => []]);
        $this->assertGatewayError(502, fn () => app(SalesGateway::class)->clientHistory(12, null, null));
    }

    public function test_empty_history_is_valid_but_server_errors_and_redirects_are_not_empty_results(): void
    {
        $this->fake(['g4.test/*' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0]])]);
        $this->assertCount(0, app(SalesGateway::class)->clientHistory(12, null, null));
        foreach ([Http::response(['message' => 'Error'], 500), Http::response('', 302, ['Location' => 'https://otro.test/login']),
            Http::response(['error' => true, 'data' => []]), Http::response('<html>Error</html>')] as $response) {
            $this->fake(['g4.test/*' => $response]);
            $this->assertGatewayError(502, fn () => app(SalesGateway::class)->clientHistory(12, null, null));
        }
    }

    public function test_transport_failure_returns_503_and_required_credentials_are_enforced(): void
    {
        $this->fake(['g4.test/*' => Http::failedConnection()]);
        $this->assertGatewayError(503, fn () => app(SalesGateway::class)->clientHistory(12, null, null));
        config(['services.sales.auth_required' => true]);
        $this->fake();
        $this->assertGatewayError(503, fn () => app(SalesGateway::class)->clientHistory(12, null, null));
        Http::assertNothingSent();
    }

    public function test_catalog_consumes_the_g3_contract_and_does_not_forward_user_identity(): void
    {
        config(['services.catalog.url' => 'https://g3.test']);
        $this->fake(['g3.test/api/v1/productos' => Http::response(['status' => 'success', 'data' => [
            ['id_producto' => 42, 'codigo' => 'P-42', 'nombre' => null, 'descripcion' => 'Yerba 1kg'],
            ['id_producto' => 12, 'codigo' => 'P-12', 'nombre' => 'Arroz', 'descripcion' => 'Bolsa'],
        ]])]);
        $products = app(ExternalCatalogGateway::class)->products();
        $this->assertSame([12, 42], $products->pluck('id')->all());
        $this->assertSame('Yerba 1kg', $products->last()->nombre);
        Http::assertSent(fn ($request) => $request->method() === 'GET' && ! $request->hasHeader('Authorization')
            && ! $request->hasHeader('X-User-Id') && ! $request->hasHeader('X-User-Username') && ! $request->hasHeader('Cookie'));
        $this->assertFalse(Schema::hasTable('PRODUCTO'));
    }

    public function test_catalog_errors_and_duplicate_ids_are_not_silently_accepted(): void
    {
        config(['services.catalog.url' => 'https://g3.test']);
        $product = ['id_producto' => 42, 'codigo' => 'P-42', 'descripcion' => 'Yerba'];
        foreach ([Http::response(['message' => 'Falta tabla'], 500), Http::response(['status' => 'error', 'data' => []]),
            Http::response(['data' => [$product, $product]])] as $response) {
            $this->fake(['g3.test/*' => $response]);
            $this->assertGatewayError(502, fn () => app(ExternalCatalogGateway::class)->products());
        }
    }

    public function test_buyers_and_ranking_are_derived_from_the_published_sales_api(): void
    {
        $this->actorAndClients();
        config(['services.catalog.url' => 'https://g3.test']);
        $this->fake(['g3.test/api/v1/productos' => Http::response(['data' => [
            ['id_producto' => 42, 'codigo' => 'P-42', 'nombre' => 'Yerba', 'descripcion' => 'Yerba'],
        ]]), 'g4.test/api/ventas*' => Http::response(['data' => [$this->sale(), $this->sale(81, 99)]])]);
        $this->get('/reportes/clientes-por-producto?producto_id=42')->assertOk()->assertSeeText('Yerba')
            ->assertSeeText('100,00%')->assertSeeText('1 de 1 clientes registrados')->assertSeeText('Comercial Norte');
        $this->get('/reportes/ranking-comercial')->assertOk()->assertSeeText('Yerba 1kg')->assertSeeText('$6.400,00')
            ->assertSeeText('Importe neto vendido');
        Http::assertSentCount(3);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/api/v1/reportes/'));
        $this->assertFalse(Schema::hasTable('venta'));
        $this->assertFalse(Schema::hasTable('PRODUCTO'));
    }

    public function test_pdf_and_excel_exports_accept_the_same_g4_resource(): void
    {
        $this->actorAndClients();
        $this->fake(['g4.test/*' => Http::response(['data' => [$this->sale()]])]);
        $this->get('/reportes/historial-clientes/exportar/pdf?cliente_id=12')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get('/reportes/historial-clientes/exportar/xlsx?cliente_id=12')->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_product_reach_uses_one_complete_sales_read_when_catalog_is_not_connected(): void
    {
        $this->actorAndClients();
        $this->fake(function ($request) {
            $page = (int) $request['page'];
            $pending = $this->sale(82, 12, 'pendiente');
            $pending['detalles'][0]['id_producto'] = 77;
            $pending['detalles'][0]['producto'] = 'Producto solo pendiente';

            return Http::response(['data' => $page === 1 ? [$this->sale(80)] : [$this->sale(81, 99), $pending],
                'meta' => ['current_page' => $page, 'last_page' => 2, 'total' => 3]]);
        });
        $this->get('/reportes/clientes-por-producto?producto_id=42')->assertOk()
            ->assertSeeText('Productos con ventas')->assertSeeText('No incluye productos sin ventas')
            ->assertSeeText('100,00%')->assertSeeText('1 de 1 clientes registrados')->assertSeeText('ID 42')
            ->assertDontSeeText('Producto solo pendiente');
        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => ! str_starts_with($request->url(), 'https://g4.test/api/ventas?'));
        $this->assertFalse(Schema::hasTable('venta'));
        $this->assertFalse(Schema::hasTable('PRODUCTO'));
    }

    public function test_sales_product_selector_distinguishes_empty_data_unknown_product_and_upstream_failure(): void
    {
        $this->actorAndClients();
        $this->fake(['g4.test/*' => Http::response(['data' => []])]);
        $this->get('/reportes/clientes-por-producto')->assertOk()->assertSeeText('No hay productos con ventas confirmadas o posteriores.');
        $this->get('/reportes/clientes-por-producto?producto_id=42')->assertNotFound();
        $this->fake(['g4.test/*' => Http::response(['message' => 'Falla de proveedor'], 500)]);
        $this->get('/reportes/clientes-por-producto')->assertStatus(502);
    }

    public function test_configured_catalog_failure_is_not_hidden_by_the_sales_selector(): void
    {
        $this->actorAndClients();
        config(['services.catalog.url' => 'https://g3.test']);
        $this->fake(['g3.test/*' => Http::response(['message' => 'Falta tabla'], 500)]);
        $this->get('/reportes/clientes-por-producto')->assertStatus(502);
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => str_starts_with($request->url(), 'https://g4.test/'));
    }
}
