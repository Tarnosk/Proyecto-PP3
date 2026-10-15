<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalGatewaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.sales', [
            'driver' => 'http',
            'url' => 'https://ventas.example.test',
            'token' => 'ventas-token',
        ]);
        Http::preventStrayRequests();
    }

    public function test_client_history_and_export_use_partner_json_with_filters_and_bearer_token(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id]);
        $sales = json_decode(file_get_contents(base_path('docs/contracts/ventas.json')), true);
        Http::fake([
            'ventas.example.test/api/v1/ventas*' => Http::response($sales),
        ]);

        $this->signInAs($admin)->get(route('reports.client-history', [
            'cliente_id' => $client->id,
            'desde' => '2026-10-01',
            'hasta' => '2026-10-06',
        ]))->assertOk()->assertSeeText('Yerba 1kg');
        $this->get(route('reports.client-history.export', [
            'format' => 'pdf',
            'cliente_id' => $client->id,
            'desde' => '2026-10-01',
            'hasta' => '2026-10-06',
        ]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'cliente_id='.$client->id)
            && str_contains($request->url(), 'desde=2026-10-01')
            && str_contains($request->url(), 'hasta=2026-10-06')
            && $request->hasHeader('Authorization', 'Bearer ventas-token'));
    }

    public function test_remote_errors_and_malformed_payloads_are_visible(): void
    {
        $client = Cliente::factory()->create();
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->signInAs($admin);

        Http::fake(['ventas.example.test/*' => Http::response(['message' => 'fallo'], 500)]);
        $this->get(route('reports.client-history', ['cliente_id' => $client->id]))->assertStatus(502);

        Http::fake(['ventas.example.test/*' => Http::response(['data' => [['fecha' => 'invalid']]])]);
        $this->get(route('reports.client-history', ['cliente_id' => $client->id]))->assertStatus(502);

        config()->set('services.sales.token', null);
        $this->get(route('reports.client-history', ['cliente_id' => $client->id]))->assertStatus(503);
    }

    public function test_client_page_uses_g2_and_g4_without_local_financial_data(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id, 'saldo' => 100]);
        config()->set('services.billing', ['url' => 'https://facturas.example.test', 'token' => 'g2-token']);
        config()->set('services.balances', ['url' => 'https://saldos.example.test', 'token' => 'g4-token']);
        Http::fake([
            'facturas.example.test/api/v1/cobros*' => Http::response(['data' => []]),
            'saldos.example.test/api/v1/clientes/*/saldo' => Http::response(['data' => [
                'cliente_id' => $client->id, 'saldo' => '40.00', 'moneda' => 'ARS',
            ]]),
            'ventas.example.test/api/v1/ventas*' => Http::response(['data' => []]),
        ]);

        $response = $this->signInAs($admin)->get(route('clientes.show', $client));
        $response
            ->assertOk()->assertSeeText('$40,00')->assertDontSeeText('$100,00');

        $this->assertDatabaseHas('clientes', ['id' => $client->id, 'saldo' => 100]);
        $this->assertDatabaseCount('cobros', 0);
    }
}
