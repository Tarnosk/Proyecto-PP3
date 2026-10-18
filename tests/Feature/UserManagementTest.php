<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_list_users_and_update_their_email(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $user = User::factory()->create([
            'usuario' => 'repartidor',
            'email' => null,
        ]);

        $this->signInAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('repartidor')
            ->assertSee('Usuarios y emails');

        $this->put(route('users.email.update', $user), [
            'email' => 'REPARTIDOR@EXAMPLE.COM',
        ])->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'email' => 'repartidor@example.com',
        ]);
    }

    public function test_email_must_be_valid_and_unique(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        User::factory()->create(['email' => 'ocupado@example.com']);
        $user = User::factory()->create(['email' => null]);

        $this->signInAs($admin)
            ->put(route('users.email.update', $user), ['email' => 'ocupado@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertNull($user->fresh()->email);
    }

    public function test_non_administrator_cannot_manage_user_emails(): void
    {
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $user = User::factory()->create(['email' => null]);

        $this->signInAs($repartidor)
            ->get(route('users.index'))
            ->assertRedirect(route('dashboard'));

        $this->put(route('users.email.update', $user), ['email' => 'nuevo@example.com'])
            ->assertRedirect(route('dashboard'));

        $this->assertNull($user->fresh()->email);
    }
}
