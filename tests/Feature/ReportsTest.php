<?php

namespace Tests\Feature;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use App\Modules\Productos\Models\Producto;
use App\Modules\Ventas\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_general_sales_and_stock_report_routes_remain_unavailable(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->signInAs($admin)->get('/reportes/ventas-diarias')->assertNotFound();
        $this->get('/reportes/stock-bajo')->assertNotFound();
    }

    public function test_unconfigured_integrations_never_present_local_sales_or_products_as_external_data(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id]);
        $product = Producto::factory()->create(['nombre' => 'Producto local']);
        Venta::factory()->create(['cliente_id' => $client->id, 'usuario_id' => $admin->id]);
        config()->set('services.sales.url', null);
        config()->set('services.catalog.url', null);

        $this->signInAs($admin)->get(route('reports.client-history', ['cliente_id' => $client->id]))
            ->assertOk()->assertSeeText('G4 publique su API de ventas')->assertDontSeeText('0 operaciones');
        $this->get(route('clientes.show', $client))
            ->assertOk()->assertSeeText('Esperando API de ventas de G4');
        $this->get(route('reports.client-product-reach', ['producto_id' => $product->id]))
            ->assertOk()->assertSeeText('G3 publique el catalogo')->assertDontSeeText('Producto local');
        $this->get(route('reports.client-history.export', ['format' => 'pdf', 'cliente_id' => $client->id]))
            ->assertServiceUnavailable();
        Http::assertNothingSent();
    }

    public function test_client_history_reads_only_g4_json_and_preserves_date_filters(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id]);
        $this->configureSales();
        Http::fake(['ventas.example.test/api/v1/ventas*' => Http::response(json_decode(
            file_get_contents(base_path('docs/contracts/ventas.json')), true
        ))]);

        $this->signInAs($admin)->get(route('reports.client-history', [
            'cliente_id' => $client->id, 'desde' => '2026-10-01', 'hasta' => '2026-10-06',
        ]))->assertOk()->assertSeeText('Yerba 1kg');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'cliente_id='.$client->id)
            && str_contains($request->url(), 'desde=2026-10-01')
            && str_contains($request->url(), 'hasta=2026-10-06')
            && $request->hasHeader('Authorization', 'Bearer ventas-token'));
    }

    public function test_client_product_reach_uses_g3_catalog_and_g4_buyer_ids(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $first = Cliente::factory()->create(['creado_por' => $admin->id, 'apellido_razon_social' => 'Cliente Norte']);
        $second = Cliente::factory()->create(['creado_por' => $admin->id, 'apellido_razon_social' => 'Cliente Sur']);
        Cliente::factory()->create(['creado_por' => $admin->id, 'apellido_razon_social' => 'Cliente Oeste']);
        $this->configureSales();
        config()->set('services.catalog.url', 'https://catalogo.example.test');
        Http::fake([
            'catalogo.example.test/api/v1/productos' => Http::response(['status' => 'success', 'data' => [[
                'id_producto' => 42, 'codigo' => 'P-42', 'nombre' => 'Yerba 1kg', 'descripcion' => 'Yerba 1kg',
            ]]]),
            'ventas.example.test/api/v1/reportes/productos/42/compradores' => Http::response(['data' => [
                ['cliente_id' => $first->id], ['cliente_id' => $second->id],
            ]]),
        ]);

        $this->signInAs($admin)->get(route('reports.client-product-reach', ['producto_id' => 42]))
            ->assertOk()->assertSeeText('66,67%')->assertSeeText('2 de 3 clientes registrados')
            ->assertSeeText('Cliente Norte')->assertSeeText('Cliente Sur')->assertDontSeeText('Cliente Oeste');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'catalogo.example.test')
            && ! $request->hasHeader('Authorization'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'ventas.example.test')
            && $request->hasHeader('Authorization', 'Bearer ventas-token'));
    }

    public function test_client_product_reach_handles_empty_catalog_unknown_product_and_invalid_buyers(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->configureSales();
        config()->set('services.catalog.url', 'https://catalogo.example.test');
        Http::fake(['catalogo.example.test/api/v1/productos' => Http::response(['data' => []])]);

        $this->signInAs($admin)->get(route('reports.client-product-reach'))
            ->assertOk()->assertSeeText('No hay productos registrados.');
        $this->get(route('reports.client-product-reach', ['producto_id' => 999999]))->assertNotFound();
    }

    public function test_client_product_reach_rejects_invalid_buyer_payload(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->configureSales();
        config()->set('services.catalog.url', 'https://catalogo.example.test');
        Http::fake([
            'catalogo.example.test/api/v1/productos' => Http::response(['data' => [[
                'id_producto' => 42, 'codigo' => 'P-42', 'nombre' => 'Yerba', 'descripcion' => 'Yerba',
            ]]]),
            'ventas.example.test/api/v1/reportes/productos/42/compradores' => Http::response(['data' => [['wrong' => 1]]]),
        ]);
        $this->signInAs($admin)->get(route('reports.client-product-reach', ['producto_id' => 42]))->assertStatus(502);
    }

    public function test_product_reach_waits_for_both_services_and_keeps_total_across_pages(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        config()->set('services.catalog.url', 'https://catalogo.example.test');
        $this->signInAs($admin)->get(route('reports.client-product-reach'))
            ->assertOk()->assertSeeText('G4 publique los compradores por producto');
        Http::assertNothingSent();

        $clients = Cliente::factory()->count(21)->create(['creado_por' => $admin->id]);
        $this->configureSales();
        Http::fake([
            'catalogo.example.test/api/v1/productos' => Http::response(['data' => [[
                'id_producto' => 42, 'codigo' => 'P-42', 'nombre' => null, 'descripcion' => 'Yerba 1kg',
            ]]]),
            'ventas.example.test/api/v1/reportes/productos/42/compradores' => Http::response(['data' => $clients->map(fn (Cliente $client) => ['cliente_id' => $client->id])->all(),
            ]),
        ]);

        $this->get(route('reports.client-product-reach', ['producto_id' => 42, 'page' => 2]))
            ->assertOk()->assertSeeText('100,00%')->assertSeeText('21 de 21 clientes registrados')
            ->assertSeeText('Página 2 de 2');
    }

    public function test_report_roles_and_external_placeholders_remain_restricted(): void
    {
        $contador = User::factory()->create(['rol' => UserRole::CONTADOR]);
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);

        $this->signInAs($contador)->get(route('reports.index'))
            ->assertOk()->assertSeeText('Historial de clientes')->assertSeeText('Clientes por producto');
        $this->signInAs($repartidor)->get(route('reports.index'))->assertRedirect(route('dashboard'));
        $this->get(route('ventas.index'))->assertOk()->assertSeeText('Ventas y pedidos');
        $this->get(route('stock.index'))->assertOk()->assertSeeText('Inventario y stock');
    }

    public function test_client_history_export_uses_g4_json_for_pdf_and_xlsx(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id]);
        $this->configureSales();
        Http::fake(['ventas.example.test/api/v1/ventas*' => Http::response(['data' => []])]);
        $this->signInAs($admin);

        $this->get(route('reports.client-history.export', ['format' => 'pdf', 'cliente_id' => $client->id]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('reports.client-history.export', ['format' => 'xlsx', 'cliente_id' => $client->id]))
            ->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function configureSales(): void
    {
        config()->set('services.sales.url', 'https://ventas.example.test');
        config()->set('services.sales.token', 'ventas-token');
    }
}
