<?php

namespace Tests\Feature;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use App\Modules\Shared\Http\Middleware\G2SchemaReady;
use App\Services\Gateways\RendicionesGateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/** Sólo directorio/cliente G1 en SQLite en memoria; G2 se representa mediante respuestas HTTP. */
class G1RendicionesConsumptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.rendiciones.url' => 'https://g2.test', 'services.rendiciones.token' => 'service-token',
            'services.rendiciones.path' => '/api/v1/rendiciones', 'services.sales.url' => null,
            'services.billing.url' => null, 'services.balances.url' => null]);
    }

    private function parentRow(int $id = 8, string $status = 'aprobada', int $client = 12): array
    {
        return ['id_rendicion' => $id, 'estado' => $status, 'fecha' => '2026-10-23 10:00:00',
            'cobros' => [['id_rendicion_cobro' => $id * 10, 'id_rendicion' => $id, 'id_cliente' => $client,
                'id_factura' => 20, 'monto' => '120.50', 'medio_pago' => 'efectivo', 'fecha_registro' => '2026-10-23 09:00:00']]];
    }

    private function fake(array $rows): void
    {
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['g2.test/*' => Http::response(['error' => false, 'data' => $rows])]);
    }

    private function actor(UserRole $role = UserRole::ADMINISTRATIVO): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            foreach (['nombre', 'usuario', 'email', 'rol', 'estado'] as $column) {
                $table->string($column)->nullable();
            }
        });
        DB::table('usuarios')->insert([
            ['id' => 41, 'nombre' => 'Repartidor G1', 'rol' => 'repartidor', 'estado' => 'activo'],
            ['id' => 99, 'nombre' => 'Otro repartidor', 'rol' => 'repartidor', 'estado' => 'activo'],
        ]);
        $token = 'g1-test-session';
        $user = new User;
        $user->forceFill(['id' => 41, 'nombre' => 'Usuario G1', 'rol' => $role, 'estado' => UserStatus::ACTIVO,
            'current_session_id' => hash('sha256', $token)]);
        $this->actingAs($user)->withSession(['session_token' => $token, 'last_activity' => now()->timestamp]);
    }

    public function test_reads_all_pages_filters_clients_and_does_not_invent_confirmation(): void
    {
        Http::fake(fn ($request) => Http::response(['data' => [$this->parentRow((int) $request['page'], 'aprobada', $request['page'] === 1 ? 99 : 12)],
            'meta' => ['current_page' => (int) $request['page'], 'last_page' => 2, 'total' => 2],
            'links' => ['next' => 'https://foreign.test/steal-token']]));
        $rows = app(RendicionesGateway::class)->clientCollections(12);
        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['rendicion_id']);
        $this->assertNull($rows[0]['confirmacion_individual']);
        $this->assertSame('aprobada', $rows[0]['estado_rendicion']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->method() === 'GET' && $request['page'] === 2
            && $request->hasHeader('Authorization', 'Bearer service-token') && ! $request->hasHeader('Cookie'));
        Http::assertNotSent(fn ($request) => ! str_starts_with($request->url(), 'https://g2.test/api/v1/rendiciones?'));
        $this->assertFalse(Schema::hasTable('RENDICION'));
    }

    public function test_unconfigured_connection_does_not_perform_http_requests(): void
    {
        config(['services.rendiciones.token' => null]);
        $snapshot = app(RendicionesGateway::class)->clientSnapshot(12);
        $this->assertFalse($snapshot['ready']);
        $this->assertStringContainsString('no está configurada', $snapshot['message']);
        Http::assertNothingSent();
    }

    public function test_missing_cobros_duplicates_wrong_parent_and_unknown_states_are_rejected(): void
    {
        $parent = $this->parentRow();
        $missing = $parent;
        unset($missing['cobros']);
        $duplicate = $parent;
        $duplicate['cobros'][] = $duplicate['cobros'][0];
        $wrongParent = $parent;
        $wrongParent['cobros'][0]['id_rendicion'] = 999;
        $unknown = $parent;
        $unknown['estado'] = 'liquidada';
        $invalidMoney = $parent;
        $invalidMoney['cobros'][0]['monto'] = '120.501';
        foreach ([[$missing], [$duplicate], [$wrongParent], [$unknown], [$invalidMoney], [$parent, $parent]] as $rows) {
            $this->fake($rows);
            try {
                app(RendicionesGateway::class)->clientCollections(12);
                $this->fail('Debía rechazar el contrato inválido.');
            } catch (HttpException $exception) {
                $this->assertSame(502, $exception->getStatusCode());
            }
        }
    }

    public function test_empty_list_is_distinct_from_upstream_failure_or_invalid_data(): void
    {
        $this->fake([]);
        $this->assertSame(['ready' => true, 'rows' => [], 'message' => null], app(RendicionesGateway::class)->clientSnapshot(12));
        foreach ([Http::response(['error' => true], 503), Http::response(['data' => [['estado' => 'aprobada']]])] as $response) {
            Http::swap((new Factory)->preventStrayRequests());
            Http::fake(['g2.test/*' => $response]);
            $snapshot = app(RendicionesGateway::class)->clientSnapshot(12);
            $this->assertFalse($snapshot['ready']);
            $this->assertSame([], $snapshot['rows']);
            $this->assertStringContainsString('Sin datos disponibles', $snapshot['message']);
        }
    }

    public function test_rendiciones_screen_remains_visible_but_disabled_and_api_keeps_503(): void
    {
        $this->actor();
        $response = $this->get('/rendiciones')->assertStatus(503)
            ->assertSee('Sin datos disponibles')->assertSee('Listado de Rendiciones')
            ->assertSee('id="g1-rendiciones-controls" disabled', false)
            ->assertSee(asset('assets/js/g1-rendiciones.js'), false)
            ->assertSee('Importe de rendiciones aprobadas hoy')->assertDontSee('Cobros Liquidados');
        $this->assertSame(1, substr_count($response->getContent(), 'data-erp-sidebar aria-label='));
        $this->getJson('/g2/api/rendiciones')->assertStatus(503)->assertJsonPath('error', true);
        $this->postJson('/g2/api/rendiciones', [])->assertStatus(503)->assertJsonPath('error', true);
        $this->assertFalse(Schema::hasTable('RENDICION'));
    }

    public function test_fallback_keeps_role_checks_and_only_lists_the_current_drivers_option(): void
    {
        $this->actor(UserRole::REPARTIDOR);
        $this->get('/rendiciones')->assertStatus(503)->assertSee('Repartidor G1')->assertDontSee('Otro repartidor');
    }

    public function test_ready_screen_keeps_the_same_g1_presentation_layer_and_json_fallback_is_not_html(): void
    {
        $this->actor();
        $this->getJson('/rendiciones')->assertStatus(503)->assertJsonPath('error', true);
        $this->mock(G2SchemaReady::class)->shouldReceive('handle')
            ->andReturnUsing(fn ($request, $next) => $next($request));
        $this->get('/rendiciones')->assertOk()->assertSee('id="g1-rendiciones-controls" disabled', false)
            ->assertSee('Importe de rendiciones aprobadas hoy')->assertSee('Comprobando disponibilidad de G2');
    }

    public function test_presentation_does_not_override_a_forbidden_role(): void
    {
        $this->actor(UserRole::CONTADOR);
        $this->get('/rendiciones')->assertRedirect(route('dashboard'));
    }

    public function test_client_page_renders_rendition_collections_separately_from_confirmed_payments(): void
    {
        $this->actor();
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            foreach (['nombre', 'apellido_razon_social', 'dni_cuit', 'email', 'estado', 'tipo_cliente'] as $column) {
                $table->string($column)->nullable();
            }
        });
        DB::table('clientes')->insert(['id' => 12, 'nombre' => 'Ana', 'apellido_razon_social' => 'Norte', 'estado' => 'activo', 'tipo_cliente' => 'minorista']);
        $this->fake([$this->parentRow()]);
        $this->get('/clientes/12')->assertOk()->assertSee('Cobros registrados en rendiciones')
            ->assertSee('120,50')->assertSee('Aprobada')->assertSee('confirmación individual')
            ->assertSee('Sin datos disponibles para el historial de pagos.');
    }
}
