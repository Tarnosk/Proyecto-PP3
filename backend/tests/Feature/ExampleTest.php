<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::where('usuario', 'admin')->first()
            ?? User::firstOrCreate(
                ['usuario' => 'admin'],
                [
                    'password_hash' => Hash::make('Admin1234'),
                    'nombre' => 'Usuario Administrativo',
                    'rol' => UserRole::ADMINISTRATIVO,
                    'estado' => UserStatus::ACTIVO,
                ]
            );
        $this->signInAs($admin);
    }

    /**
     * Verifica que las vistas principales de Blade de Grupo 3 respondan correctamente con código 200.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertViewIs('dashboard');
    }

    public function test_inventario_view_loads(): void
    {
        $response = $this->get('/inventario');
        $response->assertOk();
        $response->assertViewIs('inventario.index');
    }

    public function test_compras_view_loads(): void
    {
        $response = $this->get('/compras');
        $response->assertOk();
        $response->assertViewIs('compras.index');
    }

    public function test_proveedores_view_loads(): void
    {
        $response = $this->get('/proveedores');
        $response->assertOk();
        $response->assertViewIs('proveedores.index');
    }
}
