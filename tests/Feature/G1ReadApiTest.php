<?php

namespace Tests\Feature;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Models\Zona;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use App\Modules\Login\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class G1ReadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.internal_api.token', 'g1-read-service-test');
        config()->set('session.lifetime', 120);
    }

    public static function endpoints(): array
    {
        return [
            'health' => ['/api/v1/health'],
            'listado' => ['/api/v1/clientes'],
            'detalle' => ['/api/v1/clientes/{id}'],
        ];
    }

    private function path(string $path): string
    {
        if (! str_contains($path, '{id}')) {
            return $path;
        }

        return str_replace('{id}', (string) Cliente::factory()->create()->id, $path);
    }

    private function userToken(UserRole $role = UserRole::ADMINISTRATIVO): array
    {
        $user = User::factory()->create(['rol' => $role]);
        $token = app(ApiTokenService::class)->issue($user, request())['token'];

        return [$user, $token];
    }

    #[DataProvider('endpoints')]
    public function test_opening_a_url_without_credentials_returns_json_401_and_no_client_data(string $path): void
    {
        $this->get($this->path($path))->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'No autorizado.']);
    }

    #[DataProvider('endpoints')]
    public function test_invalid_or_missing_service_configuration_does_not_grant_access(string $path): void
    {
        $path = $this->path($path);
        $this->withToken('inventado')->getJson($path)->assertUnauthorized();
        config()->set('services.internal_api.token', null);
        $this->withToken('g1-read-service-test')->getJson($path)->assertUnauthorized();
    }

    #[DataProvider('endpoints')]
    public function test_a_valid_administrative_web_session_can_open_the_read_api(string $path): void
    {
        $path = $this->path($path);
        $this->signInAs(User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]));
        $this->get($path)->assertOk()->assertHeader('Content-Type', 'application/json');
    }

    #[DataProvider('endpoints')]
    public function test_declaring_an_actor_without_authentication_does_not_grant_access(string $path): void
    {
        $this->withHeaders(['X-User-Id' => '1', 'X-User-Role' => 'administrativo'])
            ->get($this->path($path))->assertUnauthorized();
    }

    #[DataProvider('endpoints')]
    public function test_an_authenticated_guard_without_the_bound_session_token_does_not_grant_access(string $path): void
    {
        $path = $this->path($path);
        $this->actingAs(User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]));
        $this->get($path)->assertUnauthorized()->assertExactJson(['message' => 'No autorizado.']);
    }

    public static function roleAccess(): array
    {
        $cases = [];
        foreach (self::endpoints() as $name => [$path]) {
            foreach (UserRole::cases() as $role) {
                $cases[$name.' '.$role->value] = [$path, $role,
                    $path === '/api/v1/health' || $role !== UserRole::CONTADOR ? 200 : 403];
            }
        }

        return $cases;
    }

    #[DataProvider('roleAccess')]
    public function test_user_roles_are_enforced(string $path, UserRole $role, int $status): void
    {
        $path = $this->path($path);
        [, $token] = $this->userToken($role);
        $response = $this->withToken($token)->getJson($path)->assertStatus($status);
        if ($status === 403) {
            $response->assertExactJson(['message' => 'No tiene permisos para esta operacion.']);
        }
    }

    #[DataProvider('roleAccess')]
    public function test_web_session_roles_are_enforced(string $path, UserRole $role, int $status): void
    {
        $path = $this->path($path);
        $this->signInAs(User::factory()->create(['rol' => $role]));
        $this->get($path)->assertStatus($status)->assertHeader('Content-Type', 'application/json');
    }

    #[DataProvider('endpoints')]
    public function test_an_invalid_bearer_never_falls_back_to_a_valid_web_session(string $path): void
    {
        $path = $this->path($path);
        $this->signInAs(User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]));
        $this->withToken('invalido')->get($path)->assertUnauthorized();
    }

    #[DataProvider('endpoints')]
    public function test_expired_web_sessions_are_rejected_with_json_without_redirects(string $path): void
    {
        $path = $this->path($path);
        $this->signInAs(User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]))
            ->withSession(['last_activity' => now()->subMinutes(121)->timestamp]);
        $this->get($path)->assertUnauthorized()->assertExactJson(['message' => 'No autorizado.']);
        $this->assertGuest();
    }

    #[DataProvider('endpoints')]
    public function test_replaced_web_sessions_do_not_revoke_the_new_session(string $path): void
    {
        $path = $this->path($path);
        $user = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->signInAs($user);
        $newToken = hash('sha256', 'otra-sesion');
        $user->forceFill(['current_session_id' => $newToken])->save();
        $this->get($path)->assertUnauthorized()->assertExactJson(['message' => 'No autorizado.']);
        $this->assertSame($newToken, $user->fresh()->current_session_id);
    }

    #[DataProvider('endpoints')]
    public function test_inactive_users_cannot_use_their_web_session(string $path): void
    {
        $path = $this->path($path);
        $user = User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]);
        $this->signInAs($user);
        $user->forceFill(['estado' => UserStatus::INACTIVO])->save();
        $this->get($path)->assertUnauthorized()->assertExactJson(['message' => 'No autorizado.']);
    }

    public function test_web_session_access_does_not_enable_financial_or_write_routes(): void
    {
        $client = Cliente::factory()->create();
        $this->signInAs(User::factory()->create(['rol' => UserRole::ADMINISTRATIVO]));
        $this->getJson('/api/v1/zonas')->assertOk();
        $this->getJson('/api/v1/clientes/'.$client->id.'/saldo')->assertUnauthorized();
        $this->getJson('/api/v1/clientes/'.$client->id.'/cobros')->assertUnauthorized();
        $this->postJson('/api/v1/clientes', [])->assertUnauthorized();
        $this->patchJson('/api/v1/clientes/'.$client->id, ['nombre' => 'Otro'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_bearer_requests_do_not_start_a_session_store(): void
    {
        $this->mock(StartSession::class)->shouldReceive('handle')->never();
        $this->withToken('g1-read-service-test')->getJson('/api/v1/health')->assertOk();
        $this->getJson('/api/v1/clientes')->assertOk();
    }

    public function test_web_login_cookie_authenticates_all_three_urls_without_bearer_or_acting_as(): void
    {
        config()->set('session.driver', 'file');
        app('session')->forgetDrivers();
        Auth::forgetGuards();
        User::factory()->create(['usuario' => 'browser-api-test', 'rol' => UserRole::ADMINISTRATIVO]);
        $client = Cliente::factory()->create();
        $login = $this->post('/login', ['usuario' => 'browser-api-test', 'password' => 'Password1234'])
            ->assertRedirect(route('dashboard'));
        $cookieName = config('session.cookie');
        $cookie = $login->getCookie($cookieName, false);
        $this->assertNotNull($cookie);
        $sessionIds = [app('session')->driver()->getId()];

        try {
            foreach (['/api/v1/health', '/api/v1/clientes', '/api/v1/clientes/'.$client->id] as $path) {
                Auth::forgetGuards();
                app('session')->forgetDrivers();
                $this->withUnencryptedCookie($cookieName, $cookie->getValue())->get($path)
                    ->assertOk()->assertHeader('Content-Type', 'application/json');
            }
            Auth::forgetGuards();
            app('session')->forgetDrivers();
            $this->post('/logout')->assertRedirect(route('login'));
            $sessionIds[] = app('session')->driver()->getId();
            Auth::forgetGuards();
            app('session')->forgetDrivers();
            $this->get('/api/v1/clientes')->assertUnauthorized();
            $sessionIds[] = app('session')->driver()->getId();
        } finally {
            foreach (array_unique($sessionIds) as $id) {
                @unlink(config('session.files').DIRECTORY_SEPARATOR.$id);
            }
        }
    }

    #[DataProvider('endpoints')]
    public function test_internal_service_token_can_read_with_no_store_responses(string $path): void
    {
        $response = $this->withToken('g1-read-service-test')->getJson($this->path($path))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        if ($path === '/api/v1/health') {
            $response->assertExactJson(['status' => 'ok']);
        }
    }

    #[DataProvider('endpoints')]
    public function test_inactive_revoked_expired_and_replaced_user_tokens_are_rejected(string $path): void
    {
        $path = $this->path($path);
        [$user, $token] = $this->userToken();
        $user->forceFill(['estado' => UserStatus::INACTIVO])->save();
        $this->withToken($token)->getJson($path)->assertUnauthorized();

        $user->forceFill(['estado' => UserStatus::ACTIVO])->save();
        app(ApiTokenService::class)->revoke($user, $token);
        $this->withToken($token)->getJson($path)->assertUnauthorized();

        $newToken = app(ApiTokenService::class)->issue($user, request())['token'];
        $latestToken = app(ApiTokenService::class)->issue($user, request())['token'];
        $this->withToken($newToken)->getJson($path)->assertUnauthorized();
        $this->withToken($latestToken)->getJson($path)->assertOk();
        $this->travel(121)->minutes();
        $this->withToken($latestToken)->getJson($path)->assertUnauthorized();
    }

    public function test_list_and_detail_return_consistent_client_fields_and_pagination(): void
    {
        $zone = Zona::create(['nombre' => 'Norte']);
        $client = Cliente::factory()->create([
            'nombre' => 'María', 'apellido_razon_social' => 'Pérez', 'dni_cuit' => '12345678',
            'tipo_documento' => 'dni', 'telefono' => '2923123456', 'email' => 'maria@empresa.com',
            'direccion_calle' => 'San Martín', 'direccion_numero' => '123',
            'localidad' => 'Pigüé', 'zona_id' => $zone->id, 'condicion_iva' => null,
            'tipo_cliente' => 'minorista', 'estado' => 'activo',
        ]);
        $expected = [
            'id' => $client->id, 'nombre' => 'María', 'apellido_razon_social' => 'Pérez',
            'dni_cuit' => '12345678', 'tipo_documento' => 'dni', 'telefono' => '2923123456',
            'email' => 'maria@empresa.com', 'direccion' => 'San Martín 123',
            'direccion_calle' => 'San Martín', 'direccion_numero' => '123', 'localidad' => 'Pigüé',
            'zona_id' => $zone->id, 'condicion_iva' => null, 'tipo_cliente' => 'minorista', 'estado' => 'activo',
        ];
        $this->withToken('g1-read-service-test')->getJson('/api/v1/clientes')->assertOk()->assertExactJson([
            'data' => [$expected], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 1],
        ]);
        $this->getJson('/api/v1/clientes/'.$client->id)->assertOk()->assertExactJson(['data' => $expected]);
    }

    public function test_filters_pages_and_empty_results_keep_the_expected_metadata(): void
    {
        $zone = Zona::create(['nombre' => 'Centro']);
        $one = Cliente::factory()->create(['nombre' => 'Ana', 'apellido_razon_social' => 'Comercial Norte',
            'tipo_cliente' => 'mayorista', 'zona_id' => $zone->id]);
        $two = Cliente::factory()->create(['nombre' => 'Ana', 'apellido_razon_social' => 'Comercial Sur',
            'tipo_cliente' => 'mayorista', 'zona_id' => $zone->id]);
        Cliente::factory()->create(['estado' => 'inactivo', 'zona_id' => null]);
        $this->withToken('g1-read-service-test')->getJson('/api/v1/clientes?'.http_build_query([
            'buscar' => 'Comercial', 'estado' => 'activo', 'tipo' => 'mayorista',
            'zona_id' => $zone->id, 'per_page' => 1, 'page' => 2,
        ]))->assertOk()->assertJsonPath('data.0.id', $two->id)
            ->assertJsonCount(1, 'data')->assertJsonPath('meta', [
                'current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2,
            ]);
        $this->getJson('/api/v1/clientes?buscar='.$one->dni_cuit)->assertOk()->assertJsonPath('data.0.id', $one->id);
        $this->getJson('/api/v1/clientes?buscar=sin-coincidencias')->assertOk()->assertExactJson([
            'data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0],
        ]);
    }

    public function test_bad_filters_and_missing_or_non_numeric_ids_return_json_errors(): void
    {
        $this->withToken('g1-read-service-test');
        foreach (['page' => 0, 'per_page' => 101, 'estado' => 'otro', 'tipo' => 'otro', 'zona_id' => 0] as $key => $value) {
            $this->getJson('/api/v1/clientes?'.http_build_query([$key => $value]))
                ->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $this->getJson('/api/v1/clientes/999999')->assertNotFound();
        $this->getJson('/api/v1/clientes/no-es-id')->assertNotFound();
    }

    public function test_missing_credentials_are_rejected_before_resolving_an_unknown_client(): void
    {
        $this->get('/api/v1/clientes/999999')->assertUnauthorized()
            ->assertExactJson(['message' => 'No autorizado.']);
        $this->get('/api/v1/clientes?per_page=101')->assertUnauthorized()
            ->assertExactJson(['message' => 'No autorizado.']);
    }
}
