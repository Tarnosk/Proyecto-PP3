<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\UserRole;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_repartidor_can_create_and_list_claims_for_active_client(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $cliente = Cliente::factory()->create(['creado_por' => $admin->id]);

        $this->signInAs($repartidor)
            ->post(route('reclamos.store'), [
                'cliente_id' => $cliente->id,
                'asunto' => 'Pedido incompleto',
                'descripcion' => 'Faltaron dos productos del pedido.',
                'prioridad' => 'alta',
            ])
            ->assertRedirect(route('reclamos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reclamos', [
            'cliente_id' => $cliente->id,
            'usuario_id' => $repartidor->id,
            'asunto' => 'Pedido incompleto',
            'estado' => 'abierto',
        ]);

        $this->get(route('reclamos.index'))
            ->assertOk()
            ->assertSeeText('Pedido incompleto');
    }

    public function test_claim_requires_valid_data_and_an_active_existing_client(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $repartidor = User::factory()->create(['rol' => UserRole::REPARTIDOR]);
        $inactiveClient = Cliente::factory()->create([
            'creado_por' => $admin->id,
            'estado' => ClientStatus::INACTIVO,
        ]);
        $this->signInAs($repartidor);

        $this->post(route('reclamos.store'), [])->assertSessionHasErrors([
            'cliente_id', 'asunto', 'descripcion', 'prioridad',
        ]);

        $this->post(route('reclamos.store'), [
            'cliente_id' => $inactiveClient->id,
            'asunto' => str_repeat('a', 161),
            'descripcion' => 'Detalle',
            'prioridad' => 'urgente',
        ])->assertSessionHasErrors(['cliente_id', 'asunto', 'prioridad']);

        $this->post(route('reclamos.store'), [
            'cliente_id' => 999999,
            'asunto' => 'Consulta',
            'descripcion' => 'Detalle',
            'prioridad' => 'media',
        ])->assertSessionHasErrors('cliente_id');
    }

    public function test_contador_cannot_access_or_create_claims(): void
    {
        $contador = User::factory()->create(['rol' => UserRole::CONTADOR]);
        $this->signInAs($contador);

        $this->get(route('reclamos.index'))->assertRedirect(route('dashboard'));
        $this->get(route('reclamos.create'))->assertRedirect(route('dashboard'));
        $this->post(route('reclamos.store'), [])->assertRedirect(route('dashboard'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reclamos.index'))->assertRedirect(route('login'));
    }
}
