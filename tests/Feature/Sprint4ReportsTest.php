<?php

namespace Tests\Feature;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Sprint4ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_reports_show_pending_integration_without_calling_or_reading_local_data(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id, 'saldo' => 1000]);
        config()->set('services.sales.url', null);
        config()->set('services.billing.url', null);
        config()->set('services.balances.url', null);

        $this->signInAs($admin)->get(route('reports.debtors'))
            ->assertOk()->assertSeeText('G2 publique facturas pendientes');
        $this->get(route('reports.product-ranking'))
            ->assertOk()->assertSeeText('conectar la API de Ventas');
        $this->get(route('clientes.show', $client))
            ->assertOk()->assertSeeText('Esperando API de saldos de G4')
            ->assertDontSeeText('$1.000,00');
        $this->getJson("/api/v1/clientes/{$client->id}/saldo")->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_debtors_merge_g4_balances_with_g2_invoices_and_sort_by_amount(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $first = Cliente::factory()->create(['creado_por' => $admin->id]);
        $second = Cliente::factory()->create(['creado_por' => $admin->id]);
        $this->configureServices();
        Http::fake([
            'saldos.example.test/api/v1/clientes/deudores' => Http::response(['data' => [
                ['cliente_id' => $first->id, 'saldo' => '100.00'],
                ['cliente_id' => $second->id, 'saldo' => '500.00'],
            ]]),
            'facturas.example.test/api/v1/facturas/pendientes' => Http::response(['data' => [
                ['id' => 7, 'cliente_id' => $second->id, 'numero' => 'F-7', 'vencimiento' => '2026-10-20', 'importe_pendiente' => '500.00'],
            ]]),
        ]);

        $response = $this->signInAs($admin)->get(route('reports.debtors'));
        $response->assertOk()->assertSeeText('F-7')->assertSeeInOrder(['$500,00', '$100,00']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer saldos-token'));
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer facturas-token'));
    }

    public function test_ranking_uses_g4_filter_and_shows_empty_period(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->configureServices();
        Http::fake(['ventas.example.test/api/v1/reportes/ranking-productos*' => Http::response(['data' => [
            ['producto_id' => 2, 'producto_nombre' => 'Arroz', 'cantidad_vendida' => 4, 'monto_facturado' => '800.00'],
            ['producto_id' => 1, 'producto_nombre' => 'Aceite', 'cantidad_vendida' => 10, 'monto_facturado' => '3000.00'],
        ]])]);

        $this->signInAs($admin)->get(route('reports.product-ranking', [
            'desde' => '2026-10-01', 'hasta' => '2026-10-10',
        ]))->assertOk()->assertSeeInOrder(['Aceite', 'Arroz']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'desde=2026-10-01')
            && str_contains($request->url(), 'hasta=2026-10-10'));

    }

    public function test_ranking_has_a_distinct_empty_period_state(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->configureServices();
        Http::fake(['ventas.example.test/api/v1/reportes/ranking-productos*' => Http::response(['data' => []])]);

        $this->signInAs($admin)->get(route('reports.product-ranking'))
            ->assertOk()->assertSeeText('No existen ventas');
    }

    public function test_ranking_is_paginated_without_losing_date_filters(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->configureServices();
        $rows = collect(range(1, 25))->map(fn ($id) => [
            'producto_id' => $id,
            'producto_nombre' => 'Producto '.$id,
            'cantidad_vendida' => 26 - $id,
            'monto_facturado' => '100.00',
        ])->all();
        Http::fake(['ventas.example.test/api/v1/reportes/ranking-productos*' => Http::response(['data' => $rows])]);

        $this->signInAs($admin)->get(route('reports.product-ranking', [
            'desde' => '2026-10-01', 'page' => 2,
        ]))->assertOk()->assertSeeText('Producto 21')->assertDontSeeText('Producto 20')
            ->assertSee('desde=2026-10-01');
    }

    public function test_external_errors_are_not_mistaken_for_empty_reports(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->configureServices();
        Http::fake(['saldos.example.test/*' => Http::response(['message' => 'error'], 500)]);
        $this->signInAs($admin)->get(route('reports.debtors'))->assertStatus(502);

        Http::fake(['ventas.example.test/*' => Http::response(['data' => [['producto_id' => 1]]])]);
        $this->get(route('reports.product-ranking'))->assertStatus(502);
    }

    public function test_local_payment_routes_are_removed_and_roles_are_enforced(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id]);

        $this->signInAs($admin)->post("/clientes/{$client->id}/cobros", [
            'monto_total' => 50, 'medio_pago' => 'efectivo',
        ])->assertNotFound();
        $this->assertDatabaseCount('cobros', 0);
        $this->signInAs($repartidor)->get(route('reports.debtors'))->assertRedirect(route('dashboard'));
        $this->get(route('reports.product-ranking'))->assertRedirect(route('dashboard'));
    }

    private function configureServices(): void
    {
        config()->set('services.sales.ranking_path', '/api/v1/reportes/ranking-productos');
        config()->set('services.sales', [...config('services.sales'), 'url' => 'https://ventas.example.test', 'token' => 'ventas-token']);
        config()->set('services.billing', [...config('services.billing'), 'url' => 'https://facturas.example.test', 'token' => 'facturas-token']);
        config()->set('services.balances', [...config('services.balances'), 'url' => 'https://saldos.example.test', 'token' => 'saldos-token']);
    }
}
