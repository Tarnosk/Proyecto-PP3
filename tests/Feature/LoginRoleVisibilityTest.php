<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoginRoleVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_each_role_receives_its_own_dashboard_content(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $contador = User::factory()->create(['rol' => UserRole::CONTADOR]);

        $this->signInAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Panel administrativo')
            ->assertSeeText('Lista de Usuarios')
            ->assertSeeText('Reportes y Finanzas')
            ->assertDontSeeText('Panel repartidor')
            ->assertDontSeeText('Panel contador');

        $this->signInAs($repartidor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Panel repartidor')
            ->assertSeeText('Ventas y Pedidos')
            ->assertSeeText('Inventario y Stock')
            ->assertDontSeeText('Lista de Usuarios')
            ->assertDontSeeText('Reportes contables');

        $this->signInAs($contador)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Panel contador')
            ->assertSeeText('Reportes contables')
            ->assertSeeText('Finanzas y Tesorería')
            ->assertDontSeeText('Lista de Usuarios')
            ->assertDontSeeText('Ventas y Pedidos');
    }

    public function test_guest_is_redirected_to_login_and_roles_are_enforced_on_routes(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);

        $this->signInAs($repartidor);

        $this->get(route('users.index'))->assertRedirect(route('dashboard'));
        $this->get(route('reports.index'))->assertRedirect(route('dashboard'));
        $this->get(route('clientes.index'))->assertOk();
        $this->get(route('ventas.index'))->assertOk();
        $this->get(route('stock.index'))->assertOk();
    }
}
