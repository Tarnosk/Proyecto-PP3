<?php

namespace Tests\Feature;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Models\Zona;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use App\Modules\Login\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $overrides = []): array
    {
        return [...[
            'nombre' => 'María José', 'apellido_razon_social' => 'Distribuidora 25 & Hnos. S.A.',
            'tipo_documento' => 'dni', 'dni_cuit' => '12345678', 'telefono' => '+54 (2923) 123456',
            'email' => 'maria+pedidos@empresa.com.ar', 'tipo_cliente' => 'mayorista',
            'localidad' => '25 de Mayo', 'zona' => 'Norte',
            'direccion_calle' => 'Av. 25 de Mayo', 'direccion_numero' => '123',
        ], ...$overrides];
    }

    private function admin(): User
    {
        $user = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->signInAs($user);

        return $user;
    }

    public function test_form_has_document_selector_zone_and_split_address_with_edit_values(): void
    {
        $admin = $this->admin();
        Zona::create(['nombre' => 'Zona existente']);
        $this->get(route('clientes.create'))->assertOk()
            ->assertSee('name="tipo_documento"', false)->assertSee('name="zona"', false)
            ->assertSee('name="direccion_calle"', false)->assertSee('name="direccion_numero"', false)
            ->assertSee('Zona existente')->assertDontSee('<textarea id="direccion"', false);
        $this->post(route('clientes.store'), $this->data())->assertSessionHasNoErrors();
        $client = Cliente::firstOrFail();
        $this->get(route('clientes.edit', $client))->assertOk()
            ->assertSee('value="12345678"', false)->assertSee('value="Av. 25 de Mayo"', false)
            ->assertSee('value="123"', false)->assertSee('value="Norte"', false);
        $this->assertSame($admin->id, $client->creado_por);
    }

    public function test_new_zone_is_saved_and_existing_zone_is_reused_without_case_duplicates(): void
    {
        $this->admin();
        $zone = Zona::create(['nombre' => 'Norte']);
        $this->post(route('clientes.store'), $this->data(['zona' => 'norte']))->assertSessionHasNoErrors();
        $this->assertSame($zone->id, Cliente::firstOrFail()->zona_id);
        $this->assertDatabaseCount('zonas', 1);
        $this->post(route('clientes.store'), $this->data(['zona' => 'Zona nueva', 'dni_cuit' => '23456789', 'email' => 'otra@empresa.com']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('zonas', 2);
        $this->assertDatabaseHas('clientes', ['direccion' => 'Av. 25 de Mayo 123', 'direccion_calle' => 'Av. 25 de Mayo', 'direccion_numero' => '123', 'tipo_documento' => 'dni']);
    }

    public static function invalidFields(): array
    {
        return [
            'tipo sin seleccionar' => ['tipo_documento', ''],
            'DNI corto' => ['dni_cuit', '123456'],
            'DNI largo' => ['dni_cuit', '123456789'],
            'DNI con letras' => ['dni_cuit', '1234567x'],
            'nombre con números' => ['nombre', 'Juan 123'],
            'razón social con HTML' => ['apellido_razon_social', '<img src=x>'],
            'localidad con HTML' => ['localidad', '<script>'],
            'zona con HTML' => ['zona', '<script>'],
            'calle con HTML' => ['direccion_calle', 'Calle <b>'],
            'altura con letras' => ['direccion_numero', '123A'],
            'altura negativa' => ['direccion_numero', '-10'],
            'altura demasiado larga' => ['direccion_numero', '1234567'],
            'email sin arroba' => ['email', 'cliente.gmail.com'],
            'email sin dominio completo' => ['email', 'cliente@gmail'],
            'dominio empieza con guion' => ['email', 'cliente@-gmail.com'],
            'teléfono con letras' => ['telefono', 'llamame123'],
            'teléfono solo símbolos' => ['telefono', '(---)'],
            'teléfono demasiado largo' => ['telefono', '1234567890123456'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_backend_rejects_invalid_fields_even_without_javascript(string $field, string $value): void
    {
        $this->admin();
        $this->post(route('clientes.store'), $this->data([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('zonas', 0);
    }

    public function test_cuit_length_and_duplicates_are_checked_independently_of_format(): void
    {
        $this->admin();
        $this->post(route('clientes.store'), $this->data(['tipo_documento' => 'cuit', 'dni_cuit' => '3012345678']))->assertSessionHasErrors('dni_cuit');
        $this->post(route('clientes.store'), $this->data(['tipo_documento' => 'cuit', 'dni_cuit' => '301234567890']))->assertSessionHasErrors('dni_cuit');
        $this->post(route('clientes.store'), $this->data(['tipo_documento' => 'cuit', 'dni_cuit' => '30123456789']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clientes', ['tipo_documento' => 'cuit', 'dni_cuit' => '30-12345678-9']);
        $this->post(route('clientes.store'), $this->data(['tipo_documento' => 'cuit', 'dni_cuit' => '30-12345678-9', 'email' => 'otro@empresa.com']))->assertSessionHasErrors('dni_cuit');
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_edit_updates_split_address_and_preserves_creator_and_zone(): void
    {
        $admin = $this->admin();
        $this->post(route('clientes.store'), $this->data())->assertSessionHasNoErrors();
        $client = Cliente::firstOrFail();
        $this->put(route('clientes.update', $client), $this->data(['direccion_numero' => '456', 'estado' => 'activo']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clientes', ['id' => $client->id, 'creado_por' => $admin->id, 'direccion' => 'Av. 25 de Mayo 456', 'direccion_numero' => '456']);
        $this->assertDatabaseCount('zonas', 1);
    }

    public function test_api_retains_legacy_address_and_accepts_structured_updates_without_stale_parts(): void
    {
        $admin = $this->admin();
        $this->withToken(app(ApiTokenService::class)->issue($admin, request())['token']);
        $old = ['nombre' => 'Ana', 'apellido_razon_social' => 'Comercial Norte', 'dni_cuit' => '20-12345678-9',
            'telefono' => '11-4444-5555', 'email' => 'ana@empresa.com', 'direccion' => 'Calle Original 12', 'tipo_cliente' => 'minorista'];
        $created = $this->postJson('/api/v1/clientes', $old)->assertCreated()
            ->assertJsonPath('data.direccion', 'Calle Original 12')->assertJsonPath('data.tipo_documento', 'cuit');
        $id = $created->json('data.id');
        $this->patchJson('/api/v1/clientes/'.$id, ['direccion_calle' => 'Av. Nueva', 'direccion_numero' => '45'])
            ->assertOk()->assertJsonPath('data.direccion', 'Av. Nueva 45');
        $this->patchJson('/api/v1/clientes/'.$id, ['direccion' => 'Otra Calle 67'])
            ->assertOk()->assertJsonPath('data.direccion_calle', 'Otra Calle')->assertJsonPath('data.direccion_numero', '67');
        $this->patchJson('/api/v1/clientes/'.$id, ['direccion_calle' => 'Solo una parte'])
            ->assertUnprocessable()->assertJsonValidationErrors('direccion');
        $this->patchJson('/api/v1/clientes/'.$id, ['tipo_documento' => 'dni'])
            ->assertUnprocessable()->assertJsonValidationErrors('dni_cuit');
    }

    public function test_g1_schema_fixture_does_not_recreate_foreign_domain_tables(): void
    {
        foreach (['productos', 'categorias', 'marcas', 'ventas', 'detalle_ventas', 'cobros', 'cobro_ventas'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }
        $this->assertFileDoesNotExist(database_path('migrations/2026_09_29_000002_add_sprint3_tables.php'));
    }
}
