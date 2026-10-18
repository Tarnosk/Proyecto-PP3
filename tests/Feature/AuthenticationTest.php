<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_log_in_and_login_is_recorded(): void
    {
        $user = User::factory()->create([
            'usuario' => 'repartidor',
            'password_hash' => Hash::make('Repartidor1234'),
            'intentos_fallidos' => 2,
        ]);

        $response = $this->post(route('login.store'), [
            'usuario' => 'repartidor',
            'password' => 'Repartidor1234',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_logs', ['usuario_id' => $user->id]);
        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);
        $this->assertNotNull($user->fresh()->current_session_id);
    }

    public function test_login_requires_username_and_password(): void
    {
        $this->post(route('login.store'), [])
            ->assertSessionHasErrors(['usuario', 'password']);

        $this->assertGuest();
    }

    public function test_wrong_password_increments_attempts_and_locks_after_three_failures(): void
    {
        $user = User::factory()->create([
            'usuario' => 'admin',
            'password_hash' => Hash::make('Admin1234'),
        ]);

        foreach (range(1, 3) as $attempt) {
            $this->post(route('login.store'), [
                'usuario' => 'admin',
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('usuario');

            $this->assertSame($attempt, $user->fresh()->intentos_fallidos);
        }

        $this->assertTrue($user->fresh()->bloqueado_hasta->isFuture());
        $this->assertGuest();
    }

    public function test_blocked_user_cannot_log_in_with_correct_password(): void
    {
        User::factory()->create([
            'usuario' => 'admin',
            'password_hash' => Hash::make('Admin1234'),
            'intentos_fallidos' => 3,
            'bloqueado_hasta' => now()->addMinutes(10),
        ]);

        $this->post(route('login.store'), [
            'usuario' => 'admin',
            'password' => 'Admin1234',
        ])->assertSessionHasErrors('usuario');

        $this->assertGuest();
    }

    public function test_inactive_and_unknown_users_receive_a_generic_error(): void
    {
        User::factory()->create([
            'usuario' => 'inactivo',
            'estado' => UserStatus::INACTIVO,
        ]);

        foreach (['inactivo', 'desconocido'] as $username) {
            $this->post(route('login.store'), [
                'usuario' => $username,
                'password' => 'Password1234',
            ])->assertSessionHasErrors('usuario');
        }

        $this->assertGuest();
    }

    public function test_user_can_log_out_and_current_session_is_cleared(): void
    {
        $user = User::factory()->create();
        $this->signInAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull($user->fresh()->current_session_id);
    }
}
