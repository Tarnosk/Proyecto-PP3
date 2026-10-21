<?php

namespace Tests\Feature;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiLoginTest extends TestCase
{
    use DatabaseTransactions, \Tests\UsesG3Database {
        \Tests\UsesG3Database::beginDatabaseTransaction insteadof DatabaseTransactions;
    }

    protected function setUp(): void
    {
        parent::setUp();
        User::whereIn('usuario', ['api_admin', 'api_bloqueable'])->delete();
    }

    public function test_api_login_returns_token_and_user_info(): void
    {
        $admin = User::factory()->create([
            'usuario' => 'api_admin',
            'password_hash' => Hash::make('Admin1234'),
            'rol' => UserRole::ADMINISTRATIVO,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'usuario' => 'api_admin',
            'password' => 'Admin1234',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.usuario.id', $admin->id)
            ->assertJsonPath('data.usuario.rol', 'administrativo');

        $token = $response->json('data.access_token');
        $this->assertNotEmpty($token);
        $this->assertNotEmpty($response->json('data.expires_at'));

        // Probar logout de la API
        $logoutResponse = $this->withToken($token)->postJson('/api/v1/auth/logout');
        $logoutResponse->assertOk()->assertJsonPath('message', 'Sesion cerrada.');
    }

    public function test_api_login_locks_account_after_three_failed_attempts(): void
    {
        $user = User::factory()->create([
            'usuario' => 'api_bloqueable',
            'password_hash' => Hash::make('ClaveSegura123'),
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'usuario' => 'api_bloqueable',
                'password' => 'incorrecta',
            ])->assertUnprocessable()->assertJsonValidationErrors('usuario');
        }

        $this->assertEquals(3, $user->fresh()->intentos_fallidos);
        $this->assertNotNull($user->fresh()->bloqueado_hasta);

        $this->postJson('/api/v1/auth/login', [
            'usuario' => 'api_bloqueable',
            'password' => 'ClaveSegura123',
        ])->assertUnprocessable();
    }
}
