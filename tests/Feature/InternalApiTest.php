<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InternalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.internal_api.token', 'test-internal-token');
    }

    public function test_api_rejects_missing_or_wrong_token_and_fails_closed_when_unconfigured(): void
    {
        $this->getJson('/api/v1/health')->assertUnauthorized();
        $this->withToken('wrong')->getJson('/api/v1/health')->assertUnauthorized();
        $this->withToken('test-internal-token')->getJson('/api/v1/health')
            ->assertOk()->assertExactJson(['status' => 'ok']);

        config()->set('services.internal_api.token', null);
        $this->getJson('/api/v1/health')->assertUnauthorized();
    }

    public function test_clients_are_searchable_and_financial_routes_proxy_owner_services(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $zone = Zona::query()->create(['nombre' => 'Norte']);
        $client = Cliente::factory()->create([
            'creado_por' => $admin->id,
            'apellido_razon_social' => 'Comercial Norte',
            'zona_id' => $zone->id,
            'saldo' => 750,
        ]);
        Cliente::factory()->create(['creado_por' => $admin->id, 'apellido_razon_social' => 'Comercial Sur']);
        config()->set('services.balances', ['url' => 'https://saldos.example.test', 'token' => 'g4-token']);
        config()->set('services.billing', ['url' => 'https://facturas.example.test', 'token' => 'g2-token']);
        Http::preventStrayRequests();
        Http::fake([
            'saldos.example.test/api/v1/clientes/*/saldo' => Http::response(['data' => [
                'cliente_id' => $client->id, 'saldo' => '500.00', 'moneda' => 'ARS',
            ]]),
            'facturas.example.test/api/v1/cobros*' => Http::response(['data' => [[
                'id' => 8, 'cliente_id' => $client->id, 'fecha' => '2026-10-14T10:00:00-03:00',
                'monto_total' => '250.00', 'medio_pago' => 'transferencia',
            ]]]),
        ]);
        $this->withToken('test-internal-token');

        $this->getJson('/api/v1/clientes?buscar=Norte&per_page=1')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $client->id)
            ->assertJsonMissingPath('data.0.saldo');
        $this->getJson("/api/v1/clientes/{$client->id}/saldo")
            ->assertOk()->assertJsonPath('data.saldo', '500.00')
            ->assertJsonPath('data.moneda', 'ARS');
        $this->getJson("/api/v1/clientes/{$client->id}/cobros")
            ->assertOk()
            ->assertJsonPath('data.0.monto_total', '250.00');
        $this->getJson('/api/v1/zonas')->assertOk()->assertJsonPath('data.0.nombre', 'Norte');
        $this->getJson('/api/v1/clientes/999999')->assertNotFound();
        $this->getJson('/api/v1/clientes?per_page=101')->assertUnprocessable();
    }

    public function test_claims_can_be_created_filtered_and_updated_with_json(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id]);
        $this->withToken('test-internal-token');

        $created = $this->postJson('/api/v1/reclamos', [
            'cliente_id' => $client->id,
            'usuario_id' => $repartidor->id,
            'asunto' => 'Mercaderia incompleta',
            'descripcion' => 'Faltaron dos unidades',
            'prioridad' => 'alta',
        ])->assertCreated()
            ->assertJsonPath('data.estado', 'abierto')
            ->assertJsonPath('data.usuario_id', $repartidor->id);

        $claimId = $created->json('data.id');
        $this->getJson("/api/v1/reclamos?cliente_id={$client->id}&estado=abierto")
            ->assertOk()->assertJsonPath('meta.total', 1);
        $this->patchJson("/api/v1/reclamos/{$claimId}/estado", ['estado' => 'en_proceso'])
            ->assertOk()->assertJsonPath('data.estado', 'en_proceso');
        $this->getJson("/api/v1/reclamos/{$claimId}")->assertJsonPath('data.estado', 'en_proceso');
        $this->patchJson("/api/v1/reclamos/{$claimId}/estado", ['estado' => 'desconocido'])
            ->assertUnprocessable();
        $this->postJson('/api/v1/reclamos', [
            'cliente_id' => $client->id,
            'usuario_id' => 99999,
            'asunto' => 'Otro',
            'descripcion' => 'Detalle',
            'prioridad' => 'media',
        ])->assertUnprocessable();
    }

    public function test_user_lookup_exposes_only_safe_identity_fields(): void
    {
        $user = User::factory()->create();
        $response = $this->withToken('test-internal-token')
            ->getJson("/api/v1/usuarios/{$user->id}")->assertOk();

        $this->assertSame(['id', 'nombre', 'rol', 'estado'], array_keys($response->json('data')));
        $this->assertArrayNotHasKey('password_hash', $response->json('data'));
    }
}
