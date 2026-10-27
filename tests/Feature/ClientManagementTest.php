<?php

namespace Tests\Feature;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_search_clients(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        Cliente::factory()->create([
            'creado_por' => $admin->id,
            'apellido_razon_social' => 'Comercial Norte',
            'dni_cuit' => '20-12345678-9',
            'localidad' => 'Pigue',
            'direccion' => 'San Martin 123',
        ]);
        Cliente::factory()->create(['creado_por' => $admin->id, 'apellido_razon_social' => 'Comercial Sur']);

        $this->signInAs($admin)
            ->get(route('clientes.index', ['q' => '12345678']))
            ->assertOk()
            ->assertSeeText('Comercial Norte')
            ->assertSeeText('Pigue')
            ->assertSeeText('San Martin 123')
            ->assertDontSeeText('Comercial Sur');

        $this->get(route('clientes.index', ['q' => 'Pigue']))
            ->assertOk()
            ->assertSeeText('Comercial Norte')
            ->assertDontSeeText('Comercial Sur');
    }

    public function test_repartidor_can_list_clients_but_cannot_create_or_edit_them(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $cliente = Cliente::factory()->create(['creado_por' => $admin->id]);

        $this->signInAs($repartidor);
        $this->get(route('clientes.index'))->assertOk();
        $this->get(route('clientes.create'))->assertRedirect(route('dashboard'));
        $this->get(route('clientes.edit', $cliente))->assertRedirect(route('dashboard'));
        $this->post(route('clientes.store'), $this->validData())->assertRedirect(route('dashboard'));
    }

    public function test_contador_cannot_access_client_management(): void
    {
        $contador = User::factory()->create(['rol' => UserRole::CONTADOR]);

        $this->signInAs($contador)
            ->get(route('clientes.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_client_and_is_registered_as_creator(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);

        $this->signInAs($admin)
            ->post(route('clientes.store'), $this->validData())
            ->assertRedirect(route('clientes.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clientes', [
            'dni_cuit' => '20-12345678-9',
            'localidad' => 'Pigue',
            'direccion' => 'Calle Principal 123',
            'creado_por' => $admin->id,
            'estado' => 'activo',
        ]);
    }

    public function test_client_creation_validates_missing_invalid_and_duplicate_values(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        Cliente::factory()->create([
            'creado_por' => $admin->id,
            'dni_cuit' => '20-12345678-9',
            'email' => 'cliente@example.com',
        ]);
        $this->signInAs($admin);

        $this->post(route('clientes.store'), [])->assertSessionHasErrors([
            'nombre', 'apellido_razon_social', 'tipo_documento', 'dni_cuit', 'telefono', 'email', 'localidad', 'zona', 'direccion_calle', 'direccion_numero', 'tipo_cliente',
        ]);

        $invalid = $this->validData();
        $invalid['dni_cuit'] = 'abc';
        $invalid['telefono'] = 'x';
        $invalid['email'] = 'sin-arroba';
        $invalid['localidad'] = '   ';
        $invalid['tipo_cliente'] = 'premium';
        $this->post(route('clientes.store'), $invalid)->assertSessionHasErrors(['dni_cuit', 'telefono', 'email', 'localidad', 'tipo_cliente']);

        $this->post(route('clientes.store'), $this->validData())->assertSessionHasErrors(['dni_cuit', 'email']);
    }

    public function test_admin_can_update_client_but_cannot_duplicate_unique_values(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $cliente = Cliente::factory()->create(['creado_por' => $admin->id]);
        $other = Cliente::factory()->create([
            'creado_por' => $admin->id,
            'dni_cuit' => '27-22222222-2',
            'email' => 'otro@example.com',
        ]);
        $this->signInAs($admin);

        $updated = $this->validData();
        $updated['estado'] = 'inactivo';
        $this->put(route('clientes.update', $cliente), $updated)
            ->assertRedirect(route('clientes.index'));
        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Ana',
            'localidad' => 'Pigue',
            'direccion' => 'Calle Principal 123',
            'estado' => 'inactivo',
        ]);

        $updated['dni_cuit'] = $other->dni_cuit;
        $updated['email'] = $other->email;
        $this->put(route('clientes.update', $cliente), $updated)
            ->assertSessionHasErrors(['dni_cuit', 'email']);
    }

    public function test_nonexistent_client_returns_not_found_for_admin(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);

        $this->signInAs($admin)
            ->get('/clientes/999999/editar')
            ->assertNotFound();
    }

    private function validData(): array
    {
        return [
            'nombre' => 'Ana',
            'apellido_razon_social' => 'Pérez Comercial',
            'dni_cuit' => '20-12345678-9',
            'tipo_documento' => 'cuit',
            'telefono' => '11-4444-5555',
            'email' => 'cliente@example.com',
            'localidad' => 'Pigue',
            'zona' => 'Norte',
            'direccion_calle' => 'Calle Principal',
            'direccion_numero' => '123',
            'tipo_cliente' => 'mayorista',
        ];
    }
}
