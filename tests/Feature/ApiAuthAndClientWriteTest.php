<?php

namespace Tests\Feature;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Models\Zona;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use App\Modules\Login\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthAndClientWriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_bearer_token_and_logout_revokes_it(): void
    {
        $admin = User::factory()->create([
            'usuario' => 'apiadmin',
            'password_hash' => Hash::make('ClaveSegura123'),
            'rol' => UserRole::ADMINISTRATIVO,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'usuario' => 'apiadmin',
            'password' => 'ClaveSegura123',
        ])->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.usuario.id', $admin->id);

        $token = $login->json('data.access_token');
        $this->assertNotEmpty($login->json('data.expires_at'));
        $this->assertDatabaseHas('usuarios', ['id' => $admin->id, 'current_session_id' => hash('sha256', $token)]);
        $this->assertDatabaseCount('login_logs', 1);

        $this->withToken($token)->getJson('/api/v1/clientes')->assertOk();
        $this->postJson('/api/v1/auth/logout')->assertOk()->assertJsonPath('message', 'Sesion cerrada.');
        $this->getJson('/api/v1/zonas')->assertUnauthorized();
    }

    public function test_api_login_uses_existing_failed_attempt_lockout(): void
    {
        $user = User::factory()->create([
            'usuario' => 'bloqueable',
            'password_hash' => Hash::make('ClaveSegura123'),
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'usuario' => 'bloqueable',
                'password' => 'incorrecta',
            ])->assertUnprocessable()->assertJsonValidationErrors('usuario');
        }

        $this->assertEquals(3, $user->fresh()->intentos_fallidos);
        $this->assertNotNull($user->fresh()->bloqueado_hasta);
        $this->postJson('/api/v1/auth/login', [
            'usuario' => 'bloqueable',
            'password' => 'ClaveSegura123',
        ])->assertUnprocessable();
    }

    public function test_new_login_replaces_old_api_token_and_expiration_is_enforced(): void
    {
        User::factory()->create([
            'usuario' => 'admin2',
            'password_hash' => Hash::make('ClaveSegura123'),
            'rol' => UserRole::ADMINISTRATIVO,
        ]);
        $first = $this->postJson('/api/v1/auth/login', [
            'usuario' => 'admin2', 'password' => 'ClaveSegura123',
        ])->json('data.access_token');
        $second = $this->postJson('/api/v1/auth/login', [
            'usuario' => 'admin2', 'password' => 'ClaveSegura123',
        ])->json('data.access_token');

        $this->withToken($first)->getJson('/api/v1/zonas')->assertUnauthorized();
        $this->withToken($second)->getJson('/api/v1/zonas')->assertOk();

        $this->travel(121)->minutes();
        $this->getJson('/api/v1/zonas')->assertUnauthorized();
    }

    public function test_admin_can_create_replace_and_patch_client_without_editing_balance_or_creator(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $zone = Zona::query()->create(['nombre' => 'Norte']);
        $this->withToken($this->tokenFor($admin));

        $created = $this->postJson('/api/v1/clientes', $this->clientData())
            ->assertCreated()
            ->assertJsonPath('data.estado', 'activo')
            ->assertJsonMissingPath('data.saldo');
        $id = $created->json('data.id');
        $this->assertDatabaseHas('clientes', ['id' => $id, 'creado_por' => $admin->id]);

        $replacement = [...$this->clientData(),
            'nombre' => 'Beatriz',
            'estado' => 'inactivo',
            'localidad' => 'San Isidro',
            'zona_id' => $zone->id,
            'condicion_iva' => 'Monotributo',
        ];
        $this->putJson("/api/v1/clientes/{$id}", $replacement)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Beatriz')
            ->assertJsonPath('data.zona_id', $zone->id);

        $this->patchJson("/api/v1/clientes/{$id}", ['telefono' => '11-9999-8888', 'localidad' => null])
            ->assertOk()
            ->assertJsonPath('data.telefono', '11-9999-8888')
            ->assertJsonPath('data.localidad', null)
            ->assertJsonPath('data.nombre', 'Beatriz');

        $this->assertDatabaseHas('clientes', ['id' => $id, 'creado_por' => $admin->id, 'saldo' => 0]);
        $this->patchJson("/api/v1/clientes/{$id}", ['saldo' => 100])
            ->assertUnprocessable()->assertJsonValidationErrors('saldo');
        $this->patchJson("/api/v1/clientes/{$id}", ['creado_por' => 999])
            ->assertUnprocessable()->assertJsonValidationErrors('creado_por');
    }

    public function test_write_validation_rejects_missing_fields_duplicates_and_empty_patch(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $existing = Cliente::factory()->create(['creado_por' => $admin->id]);
        $this->withToken($this->tokenFor($admin));

        $this->postJson('/api/v1/clientes', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'dni_cuit', 'email']);
        $this->putJson("/api/v1/clientes/{$existing->id}", ['nombre' => 'Incompleto'])
            ->assertUnprocessable()->assertJsonValidationErrors(['apellido_razon_social', 'estado', 'localidad']);
        $this->patchJson("/api/v1/clientes/{$existing->id}", [])
            ->assertUnprocessable()->assertJsonValidationErrors('cliente');

        $duplicate = $this->clientData();
        $duplicate['dni_cuit'] = $existing->dni_cuit;
        $this->postJson('/api/v1/clientes', $duplicate)
            ->assertUnprocessable()->assertJsonValidationErrors('dni_cuit');
        $this->patchJson("/api/v1/clientes/{$existing->id}", ['campo_desconocido' => 'x'])
            ->assertUnprocessable()->assertJsonValidationErrors('campo_desconocido');
    }

    public function test_repartidor_and_internal_service_cannot_modify_clients(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id]);
        config()->set('services.internal_api.token', 'service-token');

        $this->withToken($this->tokenFor($repartidor));
        $this->getJson('/api/v1/clientes')->assertOk();
        $this->postJson('/api/v1/clientes', $this->clientData())->assertForbidden();
        $this->putJson("/api/v1/clientes/{$client->id}", [])->assertForbidden();
        $this->patchJson("/api/v1/clientes/{$client->id}", ['telefono' => '11-1234-5678'])->assertForbidden();

        $this->withToken('service-token');
        $this->getJson('/api/v1/clientes')->assertOk();
        $this->postJson('/api/v1/clientes', $this->clientData())->assertForbidden();
        $this->postJson('/api/v1/auth/logout')->assertForbidden();
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_contador_token_cannot_read_client_or_claim_records(): void
    {
        $contador = User::factory()->create(['rol' => UserRole::CONTADOR]);
        $this->withToken($this->tokenFor($contador));

        $this->getJson('/api/v1/clientes')->assertForbidden();
        $this->getJson('/api/v1/reclamos')->assertForbidden();
        $this->getJson("/api/v1/usuarios/{$contador->id}")->assertForbidden();
    }

    public function test_client_list_details_and_writes_require_authentication(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create(['creado_por' => $admin->id]);

        $this->getJson('/api/v1/clientes')
            ->assertUnauthorized()->assertJsonMissingPath('data');
        $this->getJson("/api/v1/clientes/{$client->id}")->assertUnauthorized();
        $this->postJson('/api/v1/clientes', $this->clientData())->assertUnauthorized();
    }

    private function tokenFor(User $user): string
    {
        return app(ApiTokenService::class)->issue($user, request())['token'];
    }

    private function clientData(): array
    {
        return [
            'nombre' => 'Ana',
            'apellido_razon_social' => 'Comercial Norte',
            'dni_cuit' => '20-12345678-9',
            'telefono' => '11-4444-5555',
            'email' => 'ana@example.com',
            'direccion' => 'Calle Principal 123',
            'tipo_cliente' => 'mayorista',
        ];
    }
}
