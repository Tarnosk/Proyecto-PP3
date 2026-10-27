<?php

namespace Tests\Feature;

use App\Http\Middleware\IdentidadUsuario;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use App\Services\Gateways\ExternalFinanceGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Frontend only: no migrations, seeders, persisted users or business operations. */
class PresentationIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('Las pruebas de presentación requieren SQLite :memory: aislado.');
        }

        // El alta de Clientes consulta zonas; este fixture solo sirve a la vista.
        // Las pruebas funcionales crean el esquema con las migraciones reales.
        Schema::create('zonas', function ($table): void {
            $table->increments('id');
            $table->string('nombre', 120)->unique();
        });
    }

    private function assertSharedSidebar(string $html): void
    {
        $this->assertSame(1, substr_count($html, 'data-erp-sidebar aria-label='));
        $this->assertSame(1, substr_count($html, 'data-erp-sidebar-toggle'));
        $this->assertSame(1, substr_count($html, 'aria-label="Ir al inicio"'));
        $this->assertStringContainsString('aria-controls="erp-sidebar"', $html);
        $this->assertStringNotContainsString('<a class="app-title"', $html);
        $this->assertStringContainsString(asset('assets/css/navigation.css'), $html);
        $this->assertStringContainsString(asset('assets/js/navigation.js'), $html);
        $this->assertStringNotContainsString('id="mobileSidebar"', $html);
        $this->assertStringNotContainsString('toggleSidebarDesktop()', $html);
    }

    private function sessionAs(UserRole $role): void
    {
        $token = 'presentation-test-token';
        $user = new User;
        $user->forceFill([
            'id' => 1,
            'nombre' => 'Prueba de presentación',
            'usuario' => 'presentation-test',
            'rol' => $role,
            'estado' => UserStatus::ACTIVO,
            'current_session_id' => hash('sha256', $token),
        ]);
        $this->actingAs($user)->withSession([
            'session_token' => $token,
            'last_activity' => now()->timestamp,
        ]);
    }

    public function test_new_client_page_renders_content_form_and_scripts_without_missing_routes(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $response = $this->get('/clientes/nuevo')->assertOk();
        $this->assertSharedSidebar($response->getContent());
        $this->assertMatchesRegularExpression('/<a[^>]*title="Clientes"[^>]*aria-current="page"/', $response->getContent());
        $response->assertSee('Nuevo cliente')->assertSee('name="dni_cuit"', false)
            ->assertSee('action="'.route('clientes.store').'"', false)
            ->assertSee(asset('assets/js/app.js'), false)
            ->assertSee(asset('css/modules.css'), false);

        preg_match_all('/href="([^"]+)"/', $response->getContent(), $links);
        foreach ($links[1] as $url) {
            if (str_starts_with($url, url('/')) && ! preg_match('/\.(css|js)$/', $url)) {
                $matched = Route::getRoutes()->match(Request::create(html_entity_decode($url)));
                $this->assertContains('GET', $matched->methods(), $url);
            }
        }
    }

    public function test_legacy_spanish_sections_and_modern_stacks_both_render(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $html = Blade::render("@extends('layouts.app') @section('titulo', 'Pantalla compatible') @section('contenido')<p>Contenido de G2</p>@endsection");
        $this->assertStringContainsString('<title>Pantalla compatible</title>', $html);
        $this->assertStringContainsString('<p>Contenido de G2</p>', $html);
    }

    public function test_g1_lists_forms_and_reports_keep_their_visible_content(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $errors = new ViewErrorBag;
        $this->view('clientes.index', ['clientes' => collect(), 'search' => '', 'errors' => $errors])
            ->assertSee('Listado y búsqueda de clientes registrados.');
        $this->view('reclamos.create', ['clientes' => collect(), 'errors' => $errors])
            ->assertSee('name="cliente_id"', false)->assertSee(route('reclamos.store'), false);
        $this->view('users.index', ['users' => collect(), 'errors' => $errors])
            ->assertSee('Usuarios');
        $this->view('reports.index', ['errors' => $errors])->assertSee('Reportes');
    }

    public function test_g4_views_render_their_content_styles_and_scripts(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $this->view('ventas.ventas', ['clientes' => [], 'productos' => []])
            ->assertSee('Punto de Venta')->assertSee('btnConfirmarCobro')
            ->assertSee('const URL_VENTAS', false)->assertSee('<style>', false);
        $this->view('saldos.saldo', ['clientes' => []])
            ->assertSee('Cuentas Corrientes')->assertSee('const URL_MOVIMIENTOS', false);
        $this->view('historial-ventas.historial-ventas', [
            'ventas' => collect(), 'clientesFiltro' => [], 'productosFiltro' => [],
            'kpis' => ['cantidad' => 0, 'total_vendido' => 0, 'descuentos' => 0, 'en_cc' => 0],
        ])->assertSee('HISTORIAL DE VENTAS')->assertSee('async function llamarApi', false);
    }

    public function test_promotions_loads_the_recovered_script_without_a_vite_manifest(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $this->get('/promociones')->assertOk()->assertSee('NUEVA PROMO')
            ->assertSee(asset('js/promociones.js'), false);
        $this->assertFileExists(public_path('js/promociones.js'));
    }

    public function test_g3_screens_keep_their_frontend_and_receive_shared_navigation(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        // The identity bridge can write legacy users. Do not invoke it in UI tests.
        $this->mock(IdentidadUsuario::class)->shouldReceive('handle')
            ->andReturnUsing(fn ($request, $next) => $next($request));
        foreach (['/inventario', '/compras', '/proveedores', '/sugerencias-reposicion', '/dashboard-reposicion'] as $path) {
            $response = $this->get($path)->assertOk()->assertSee(route('ventas.index'), false)
                ->assertSee(route('clientes.index'), false)->assertSee(route('promociones.index'), false);
            $this->assertSharedSidebar($response->getContent());
        }
        $this->get('/stock')->assertRedirect('/inventario');
    }

    public function test_g2_without_its_schema_keeps_navigation_and_returns_an_installation_notice(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        foreach (['/caja', '/conciliacion', '/entregas'] as $path) {
            $response = $this->get($path)->assertStatus(503)->assertViewIs('modules.g2-unavailable')
                ->assertSee('se complete la instalación de G2');
            $this->assertSharedSidebar($response->getContent());
        }
    }

    public function test_presentation_routes_require_an_authenticated_session(): void
    {
        foreach (['/clientes/nuevo', '/ventas', '/historial-ventas', '/saldo', '/promociones', '/caja', '/conciliacion', '/entregas', '/rendiciones'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_navigation_and_presentation_permissions_follow_each_role(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR);
        $this->get('/promociones')->assertRedirect(route('dashboard'));
        $this->get('/saldo')->assertRedirect(route('dashboard'));
        $this->view('partials.sidebar-links', ['navigationStyle' => 'plain'])
            ->assertSee('Ventas y Pedidos')->assertDontSee('Lista de Usuarios')
            ->assertDontSee('Promociones')->assertDontSee('Cuentas corrientes');

        $this->sessionAs(UserRole::CONTADOR);
        $this->get('/ventas')->assertRedirect(route('dashboard'));
        $this->view('partials.sidebar-links', ['navigationStyle' => 'plain'])
            ->assertSee('Cuentas corrientes')->assertSee('Conciliación Bancaria')
            ->assertDontSee('Ventas y Pedidos')->assertDontSee('Inventario y Stock');
    }

    public function test_sales_route_points_to_the_existing_pos_instead_of_a_placeholder(): void
    {
        $route = Route::getRoutes()->getByName('ventas.index');
        $this->assertSame('App\\Modules\\Ventas\\Controllers\\VentaController@index', $route->getActionName());
        $this->assertSame('/ventas', route('ventas.index', [], false));
    }

    public function test_dashboard_g1_and_g4_share_the_same_navigation_links(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $pages = [
            view('dashboard', [
                'clientes' => collect(), 'totalClientes' => 0, 'totalMayoristas' => 0,
                'totalMinoristas' => 0, 'totalReclamos' => 0,
            ])->render(),
            $this->get('/clientes/nuevo')->assertOk()->getContent(),
            view('ventas.ventas', ['clientes' => [], 'productos' => []])->render(),
            view('saldos.saldo', ['clientes' => []])->render(),
        ];
        $expectedLinks = null;
        foreach ($pages as $html) {
            $this->assertSharedSidebar($html);
            preg_match('/<nav[^>]+data-erp-sidebar-nav[^>]*>(.*?)<\/nav>/s', $html, $navigation);
            preg_match_all('/href="([^"]+)"/', $navigation[1], $links);
            if ($expectedLinks === null) {
                $expectedLinks = $links[1];
            }
            $this->assertSame($expectedLinks, $links[1]);
        }
    }

    public function test_report_hub_buttons_point_to_the_four_g1_reports_and_existing_operational_pages(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $response = $this->get('/reportes')->assertOk()->assertSee('Reportes y Finanzas');
        $html = $response->getContent();
        $hub = substr($html, strpos($html, '<div class="report-hub">'));
        foreach (['reports.client-history', 'reports.client-product-reach', 'reports.debtors',
            'reports.product-ranking', 'ventas.historial', 'inventario.index'] as $name) {
            $this->assertStringContainsString('href="'.route($name).'"', $hub);
            $route = Route::getRoutes()->match(Request::create(route($name)));
            $this->assertContains('GET', $route->methods());
        }
        $this->assertSame(6, preg_match_all('/<a class="button button-primary"/', $hub));
        $this->assertStringNotContainsString('Consultas de otros módulos', $hub);
        $this->assertSharedSidebar($html);
    }

    public function test_accountant_keeps_g1_reports_without_links_to_modules_forbidden_to_the_role(): void
    {
        $this->sessionAs(UserRole::CONTADOR);
        $html = $this->get('/reportes')->assertOk()->getContent();
        $hub = substr($html, strpos($html, '<div class="report-hub">'));
        $this->assertStringNotContainsString('href="'.route('ventas.historial').'"', $hub);
        $this->assertStringNotContainsString('href="'.route('inventario.index').'"', $hub);
        $this->assertSame(2, substr_count($hub, 'type="button" disabled'));
        $this->assertSame(4, preg_match_all('/<a class="button button-primary"/', $hub));
    }

    public function test_report_service_error_keeps_navigation_retry_filters_and_the_actual_failure_status(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $finance = $this->mock(ExternalFinanceGateway::class);
        $finance->shouldReceive('productRankingAvailable')->twice()->andReturn(true);
        $finance->shouldReceive('productRanking')->twice()
            ->andThrow(new HttpException(503, 'Servicio en mantenimiento'));
        $this->get('/reportes/ranking-comercial?desde=2026-10-01')->assertStatus(503)
            ->assertSee('Volver a reportes')->assertSee('Reintentar consulta')
            ->assertSee('href="/reportes/ranking-comercial?desde=2026-10-01"', false);
        $this->getJson('/reportes/ranking-comercial')->assertStatus(503)->assertJsonPath('message', 'Servicio en mantenimiento');
    }
}
