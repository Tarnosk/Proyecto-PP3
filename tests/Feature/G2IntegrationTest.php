<?php

namespace Tests\Feature;

use App\Modules\Caja\Services\CajaService;
use App\Modules\ConciliacionBancaria\Services\ConciliacionService;
use App\Modules\Entregas\Integraciones\VentasApiClient;
use App\Modules\Entregas\Services\EntregaService;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use App\Modules\Rendiciones\Services\RendicionService;
use App\Modules\Shared\Http\Middleware\G2SchemaReady;
use App\Modules\Shared\Services\UsuarioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;
use UnexpectedValueException;

/** SQLite en memoria y servicios simulados: no toca la base configurada en .env. */
class G2IntegrationTest extends TestCase
{
    private function sessionAs(UserRole $role, int $id = 41): void
    {
        $token = 'g2-integration-test';
        $user = new User;
        $user->forceFill(['id' => $id, 'nombre' => 'Usuario real de prueba', 'usuario' => 'g2-test',
            'rol' => $role, 'estado' => UserStatus::ACTIVO, 'current_session_id' => hash('sha256', $token)]);
        $this->actingAs($user)->withSession(['session_token' => $token, 'last_activity' => now()->timestamp]);
    }

    private function directory(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            foreach (['nombre', 'usuario', 'email', 'rol', 'estado'] as $column) $table->string($column)->nullable();
        });
        DB::table('usuarios')->insert([
            ['id' => 41, 'nombre' => 'Repartidor real', 'rol' => 'repartidor', 'estado' => 'activo'],
            ['id' => 42, 'nombre' => 'Repartidor inactivo', 'rol' => 'repartidor', 'estado' => 'inactivo'],
            ['id' => 27, 'nombre' => 'Administración real', 'rol' => 'administrativo', 'estado' => 'activo'],
        ]);
    }

    private function ownershipTables(): void
    {
        foreach (['entrega' => 'id_entrega', 'RENDICION' => 'id_rendicion'] as $tableName => $key) {
            Schema::create($tableName, function (Blueprint $table) use ($key) {
                $table->integer($key)->primary();
                $table->integer('id_repartidor');
            });
            DB::table($tableName)->insert([[$key => 8, 'id_repartidor' => 41], [$key => 9, 'id_repartidor' => 99]]);
        }
        Schema::create('remito', function (Blueprint $table) {
            $table->integer('id_remito')->primary();
            $table->integer('id_entrega');
        });
        DB::table('remito')->insert([['id_remito' => 8, 'id_entrega' => 8], ['id_remito' => 9, 'id_entrega' => 9]]);
    }

    public function test_schema_guard_stops_business_handlers_and_returns_json(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO);
        $this->mock(CajaService::class)->shouldNotReceive('abrirCaja');
        $this->postJson('/g2/api/caja/apertura', ['monto_inicial' => 100])->assertStatus(503)->assertJsonPath('error', true);
        $this->assertFalse(Schema::hasTable('CAJA'));
    }

    public function test_directory_uses_active_g1_users_without_fabricated_identities(): void
    {
        $this->directory();
        $this->assertSame([41], array_column(UsuarioService::obtenerRepartidores(), 'id_usuario'));
        $this->assertNull(UsuarioService::obtenerUsuario(1));
        $this->assertNull(UsuarioService::obtenerUsuario(42));
        $this->assertSame('admin', UsuarioService::obtenerUsuario(27)['rol']);
    }

    public function test_caja_actor_and_role_come_from_session_despite_forged_payload(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR);
        $this->withoutMiddleware(G2SchemaReady::class);
        $this->mock(CajaService::class)->shouldReceive('abrirCaja')->once()
            ->with(41, 'repartidor', 150.0, null)->andReturn(['id_caja' => 18, 'monto_inicial' => 150]);
        $this->postJson('/g2/api/caja/apertura', ['monto_inicial' => 150, 'id_usuario' => 3, 'rol' => 'admin'])
            ->assertCreated()->assertJsonPath('data.id_caja', 18);
        // Una segunda respuesta en el mismo proceso prueba que Response ya no ejecuta exit.
        $this->getJson('/g2/api/caja/2026-10-22/99')->assertForbidden();
    }

    public function test_web_caja_opens_for_real_session_user(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO, 27);
        $this->withoutMiddleware(G2SchemaReady::class);
        $this->mock(CajaService::class)->shouldReceive('abrirCaja')->once()
            ->with(27, 'admin', 80.0, null)->andReturn(['id_caja' => 1, 'monto_inicial' => 80]);
        $this->post('/caja/apertura', ['monto_inicial' => 80])->assertRedirect(route('caja.dia'));
    }

    public function test_caja_web_does_not_expose_another_drivers_cashbox(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR);
        $this->withoutMiddleware(G2SchemaReady::class);
        $this->mock(CajaService::class)->shouldReceive('obtenerCaja')->once()->with(9)
            ->andReturn(['id_caja' => 9, 'id_usuario' => 99, 'estado' => 'abierta']);
        $this->get('/caja/pendiente/9')->assertNotFound();
    }

    public function test_conciliacion_get_is_read_only_and_post_uses_session_identity(): void
    {
        $this->sessionAs(UserRole::CONTADOR, 27);
        $this->withoutMiddleware(G2SchemaReady::class);
        $service = $this->mock(ConciliacionService::class);
        $service->shouldReceive('listarPeriodos')->once()->with(null)->andReturn([]);
        $this->get('/conciliacion')->assertOk()->assertSee('Crear período del mes si falta');
        $service->shouldReceive('asegurarPeriodoAutomaticoDelMes')->once()->with(27)->andReturn([]);
        $this->post('/conciliacion/mes')->assertRedirect(route('conciliacion.index'));
        $service->shouldReceive('crearPeriodo')->once()->with(Mockery::on(fn ($data) => $data['id_usuario'] === 27))
            ->andReturn(['id_periodo' => 15]);
        $this->postJson('/g2/api/conciliacion', ['fecha_desde' => '2026-10-01', 'fecha_hasta' => '2026-10-31', 'id_usuario' => 1])
            ->assertCreated()->assertJsonPath('data.id_periodo', 15);
    }

    public function test_delivery_lists_are_scoped_and_forged_dispatch_is_forbidden(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR);
        $this->withoutMiddleware(G2SchemaReady::class);
        $this->mock(EntregaService::class)->shouldReceive('listarEntregas')->once()
            ->with(['repartidor_id' => 41])->andReturn([]);
        $this->getJson('/g2/api/entregas?repartidor_id=99')->assertOk()->assertJsonPath('data', []);
        $this->postJson('/g2/api/entregas', ['repartidor_id' => 99, 'usuario_id' => 1])->assertForbidden();
    }

    public function test_delivery_and_remito_details_cannot_cross_driver_boundaries(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR);
        $this->withoutMiddleware(G2SchemaReady::class);
        $this->ownershipTables();
        $this->mock(EntregaService::class)->shouldReceive('obtenerEntrega')->once()->with(8)->andReturn(['id_entrega' => 8]);
        $this->getJson('/g2/api/entregas/9')->assertNotFound();
        $this->getJson('/g2/api/remitos/9')->assertNotFound();
        $this->patchJson('/g2/api/entregas/9/pedidos/50/entregado')->assertNotFound();
        $this->getJson('/g2/api/entregas/8')->assertOk()->assertJsonPath('data.id_entrega', 8);
    }

    public function test_dispatch_and_confirmation_ignore_browser_actor_ids(): void
    {
        $this->directory();
        $this->sessionAs(UserRole::ADMINISTRATIVO, 27);
        $this->withoutMiddleware(G2SchemaReady::class);
        $this->ownershipTables();
        $service = $this->mock(EntregaService::class);
        $service->shouldReceive('armarEntrega')->once()->with(41, [10], '2026-10-22', 27, null)
            ->andReturn(['id_entrega' => 8, 'mensaje' => 'Entrega de prueba']);
        $this->postJson('/g2/api/entregas', ['repartidor_id' => 41, 'pedidos_ids' => [10],
            'fecha_salida' => '2026-10-22', 'usuario_id' => 1])->assertCreated();
        $this->sessionAs(UserRole::REPARTIDOR);
        $service->shouldReceive('confirmarPedidoEntregado')->once()->with(8, 10, null, 41)
            ->andReturn(['id_entrega' => 8, 'mensaje' => 'Entrega de prueba']);
        $this->patchJson('/g2/api/entregas/8/pedidos/10/entregado', ['usuario_id' => 1])->assertOk();
    }

    public function test_rendicion_ownership_admin_review_and_actor(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR);
        $this->withoutMiddleware(G2SchemaReady::class);
        $this->ownershipTables();
        $service = $this->mock(RendicionService::class);
        $service->shouldReceive('listar')->once()->with(['id_repartidor' => 41])->andReturn([]);
        $this->getJson('/g2/api/rendiciones?id_repartidor=99')->assertOk();
        $this->getJson('/g2/api/rendiciones/9')->assertNotFound();
        $this->getJson('/g2/api/rendiciones/preparar/9')->assertNotFound();
        $this->patchJson('/g2/api/rendiciones/8/revisar', ['accion' => 'aprobar', 'id_usuario_validador' => 27])->assertForbidden();
        $this->sessionAs(UserRole::ADMINISTRATIVO, 27);
        $service->shouldReceive('revisarRendicion')->once()->with(8, Mockery::on(fn ($data) => $data['id_usuario_validador'] === 27))
            ->andReturn(['id_rendicion' => 8, 'estado' => 'aprobada']);
        $this->patchJson('/g2/api/rendiciones/8/revisar', ['accion' => 'aprobar', 'id_usuario_validador' => 3])
            ->assertOk()->assertJsonPath('data.estado', 'aprobada');
    }

    public function test_g2_restored_views_use_shared_sidebar_and_assets(): void
    {
        $this->directory();
        $this->sessionAs(UserRole::ADMINISTRATIVO, 27);
        $this->withoutMiddleware(G2SchemaReady::class);
        foreach (['entregas', 'rendiciones'] as $module) {
            $response = $this->get('/'.$module)->assertOk()->assertSee(asset('js/g2-'.$module.'.js'), false)
                ->assertSee(asset('js/g2-http.js'), false)->assertDontSee('Diego García');
            $this->assertSame(1, substr_count($response->getContent(), 'data-erp-sidebar aria-label='));
            $this->assertStringNotContainsString('id="sidebarToggle"', $response->getContent());
        }
    }

    public function test_http_client_never_falls_back_to_local_tables_or_mock_data(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://g4.test/api/ventas*' => Http::response(['data' => [
            ['id_venta' => 10, 'id_cliente' => 20, 'total' => 100, 'estado' => 'confirmada'],
            ['id_venta' => 11, 'id_cliente' => 20, 'total' => 100, 'estado' => 'anulada'],
        ]])]);
        config(['g2.ventas_url' => 'https://g4.test/api', 'g2.ventas_token' => 'test-token']);
        $this->assertSame([10], array_column((new VentasApiClient)->obtenerVentasPendientesDespacho(), 'id_venta'));
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['estado'] === 'confirmada');
        config(['g2.ventas_url' => null]);
        $this->expectException(UnexpectedValueException::class);
        (new VentasApiClient)->obtenerVentasPendientesDespacho();
    }

    public function test_http_client_reads_all_g4_resource_pages_and_preserves_real_product_ids(): void
    {
        config(['g2.ventas_url' => 'https://g4.test/api']);
        Http::preventStrayRequests();
        Http::fake(fn ($request) => Http::response(['data' => [[
            'id' => (int) $request['page'], 'cliente' => ['id' => 15, 'nombre' => 'Cliente real'],
            'estado' => ['valor' => 'confirmada'], 'total' => 20,
            'detalles' => [['id_producto' => 21, 'producto' => 'Producto real', 'cantidad' => 2]],
        ]], 'meta' => ['last_page' => 2]]));
        $sales = (new VentasApiClient)->obtenerVentasPendientesDespacho();
        $this->assertSame([1, 2], array_column($sales, 'id_venta'));
        $this->assertSame(21, $sales[0]['items'][0]['id_producto']);
        $this->assertSame('Producto real', $sales[0]['items'][0]['producto_nombre']);
        $this->assertArrayNotHasKey('pagado', $sales[0]);
        Http::assertSentCount(2);
    }

    public function test_automatic_reconciliation_reports_its_missing_cobro_dependency(): void
    {
        $this->sessionAs(UserRole::CONTADOR, 27);
        foreach (G2SchemaReady::TABLES['conciliacion'] as $table) Schema::create($table, fn (Blueprint $schema) => $schema->id());
        $this->mock(ConciliacionService::class)->shouldNotReceive('conciliarAutomatico');
        $this->post('/conciliacion/1/conciliar')->assertStatus(503)->assertSee('integración de cobros');
    }
}
