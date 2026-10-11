<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
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
