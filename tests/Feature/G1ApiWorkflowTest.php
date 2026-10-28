<?php

namespace Tests\Feature;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Models\Zona;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use App\Modules\Login\Services\ApiTokenService;
use App\Modules\Reclamos\Models\Reclamo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class G1ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public static function reads(): array
    {
        return [['zonas'], ['reclamos'], ['reclamos/{claim}'], ['usuarios/{user}']];
    }

    private function fixture(string $path, UserRole $role = UserRole::ADMINISTRATIVO): array
    {
        $user = User::factory()->create(['rol' => $role]);
        $client = Cliente::factory()->create();
        Zona::create(['nombre' => 'Centro']);
        $claim = new Reclamo;
        $claim->forceFill(['cliente_id' => $client->id, 'usuario_id' => $user->id,
            'asunto' => 'Pedido incompleto', 'descripcion' => 'Faltaron unidades', 'prioridad' => 'media', 'estado' => 'abierto']);
        $claim->save();

        return [$user, '/api/v1/'.str_replace(['{claim}', '{user}'], [(string) $claim->id, (string) $user->id], $path)];
    }

    #[DataProvider('reads')]
    public function test_new_read_routes_accept_an_admin_web_session_and_reject_guests(string $path): void
    {
        [$user, $url] = $this->fixture($path);
        $this->get($url)->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
        $this->signInAs($user);
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    #[DataProvider('reads')]
    public function test_roles_and_expired_sessions_are_enforced_for_the_new_read_routes(string $path): void
    {
        [$user, $url] = $this->fixture($path, UserRole::CONTADOR);
        $this->signInAs($user)->get($url)->assertForbidden();
        $this->withSession(['last_activity' => now()->subMinutes(121)->timestamp]);
        $this->get($url)->assertUnauthorized();
    }

    #[DataProvider('reads')]
    public function test_repartidor_permissions_match_the_contract(string $path): void
    {
        [$user, $url] = $this->fixture($path, UserRole::REPARTIDOR);
        $this->signInAs($user)->get($url)->assertStatus(str_starts_with($path, 'usuarios/') ? 403 : 200);
    }

    public function test_complete_bearer_workflow_persists_post_put_patch_claims_and_logout(): void
    {
        $user = User::factory()->create(['usuario' => 'flujo-api', 'rol' => UserRole::ADMINISTRATIVO]);
        $token = $this->postJson('/api/v1/auth/login', ['usuario' => 'flujo-api', 'password' => 'Password1234'])
            ->assertOk()->json('data.access_token');
        $this->withToken($token);
        $data = ['nombre' => 'Ana', 'apellido_razon_social' => 'Pérez', 'tipo_documento' => 'dni', 'dni_cuit' => '81234567',
            'telefono' => '2923123456', 'email' => 'flujo@erp.example', 'tipo_cliente' => 'minorista',
            'direccion_calle' => 'San Martín', 'direccion_numero' => '120', 'localidad' => 'Pigüé', 'zona' => 'Norte'];
        $id = $this->postJson('/api/v1/clientes', $data)->assertCreated()->assertJsonPath('data.direccion', 'San Martín 120')->json('data.id');
        $this->assertDatabaseHas('clientes', ['id' => $id, 'nombre' => 'Ana', 'creado_por' => $user->id]);
        $zone = $this->getJson('/api/v1/zonas')->assertOk()->json('data.0.id');
        unset($data['zona']);
        $replacement = [...$data, 'nombre' => 'Beatriz', 'estado' => 'activo', 'zona_id' => $zone, 'condicion_iva' => null];
        $this->putJson('/api/v1/clientes/'.$id, $replacement)->assertOk()->assertJsonPath('data.nombre', 'Beatriz');
        $this->patchJson('/api/v1/clientes/'.$id, ['direccion_calle' => 'Belgrano', 'direccion_numero' => '450'])
            ->assertOk()->assertJsonPath('data.direccion', 'Belgrano 450');
        $this->getJson('/api/v1/clientes/'.$id)->assertOk()->assertJsonPath('data.nombre', 'Beatriz');
        $claimId = $this->postJson('/api/v1/reclamos', ['cliente_id' => $id, 'asunto' => 'Prueba API',
            'descripcion' => 'Control de entrega', 'prioridad' => 'alta'])->assertCreated()
            ->assertJsonPath('data.usuario_id', $user->id)->json('data.id');
        $this->patchJson('/api/v1/reclamos/'.$claimId.'/estado', ['estado' => 'cerrado'])
            ->assertOk()->assertJsonPath('data.estado', 'cerrado');
        $this->assertDatabaseHas('reclamos', ['id' => $claimId, 'estado' => 'cerrado', 'usuario_id' => $user->id]);
        $this->getJson('/api/v1/reclamos?cliente_id='.$id)->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/reclamos/'.$claimId)->assertOk()->assertJsonPath('data.estado', 'cerrado');
        $this->getJson('/api/v1/usuarios/'.$user->id)->assertOk()->assertJsonMissingPath('data.password_hash');
        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->getJson('/api/v1/clientes')->assertUnauthorized();
    }

    public function test_user_claims_cannot_be_attributed_to_someone_else_but_service_actor_is_explicit(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $other = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $client = Cliente::factory()->create();
        $token = app(ApiTokenService::class)->issue($admin, request())['token'];
        $payload = ['cliente_id' => $client->id, 'usuario_id' => $other->id, 'asunto' => 'Reclamo', 'descripcion' => 'Detalle', 'prioridad' => 'media'];
        $this->withToken($token)->postJson('/api/v1/reclamos', $payload)->assertUnprocessable()->assertJsonValidationErrors('usuario_id');
        $this->assertDatabaseCount('reclamos', 0);
        config()->set('services.internal_api.token', 'actor-service-test');
        $this->withToken('actor-service-test')->postJson('/api/v1/reclamos', $payload)->assertCreated()->assertJsonPath('data.usuario_id', $other->id);
    }

    public function test_writes_reject_guest_cookie_only_and_invalid_payloads_without_persisting(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create();
        $this->postJson('/api/v1/clientes', [])->assertUnauthorized();
        $this->signInAs($admin)->postJson('/api/v1/clientes', [])->assertUnauthorized();
        $token = app(ApiTokenService::class)->issue($admin, request())['token'];
        $this->withToken($token)->postJson('/api/v1/clientes', [])->assertUnprocessable();
        $this->putJson('/api/v1/clientes/'.$client->id, ['nombre' => 'Incompleto'])->assertUnprocessable();
        $this->patchJson('/api/v1/clientes/'.$client->id, ['saldo' => 999])->assertUnprocessable();
        $this->postJson('/api/v1/reclamos', ['cliente_id' => 999999])->assertUnprocessable();
        $this->patchJson('/api/v1/reclamos/999999/estado', ['estado' => 'cerrado'])->assertNotFound();
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('reclamos', 0);
    }
}
