<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Services\CatalogoGateway;
use App\Modules\Catalogo\Services\InternalCatalogApi;
use App\Modules\Login\Models\User;
use App\Modules\Productos\Controllers\ProductoController;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class G1CatalogApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['catalogo.transport' => 'http', 'services.catalog.url' => 'https://g3.test',
            'services.catalog.token' => null, 'services.catalog.auth_required' => false,
            'services.catalog.products_path' => '/api/v1/productos']);
    }

    private function product(int $id = 1): array
    {
        return ['id_producto' => $id, 'codigo' => 'PRUEBA-'.$id, 'nombre' => 'Aceite 1,5 L',
            'descripcion' => 'Botella de aceite', 'estado' => 'activo', 'stock_disponible' => 50,
            'stock_minimo' => 5, 'precio_unitario' => '2500.00', 'id_categoria' => 1,
            'categoria_nombre' => 'Almacén', 'id_marca' => 2, 'marca_nombre' => 'Marca',
            'formato_bulto' => 'Botella', 'unidades_por_bulto' => 6,
            'id_usuario_carga' => 991, 'usuario_carga_nombre' => 'PERSONA_NO_PUBLICAR'];
    }

    public function test_public_json_list_and_detail_expose_only_the_commercial_projection(): void
    {
        Http::fake(['g3.test/api/v1/productos/1' => Http::response(['data' => $this->product()]),
            'g3.test/api/v1/productos*' => Http::response(['data' => [$this->product()]])]);
        $response = $this->get('/api/v1/catalogo')->assertOk()->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('data.0.codigo', 'PRUEBA-1')->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.disponibilidad', 'disponible')->assertJsonPath('data.0.precio', '2500.00')
            ->assertJsonMissingPath('data.0.id_usuario_carga')->assertDontSee('PERSONA_NO_PUBLICAR');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get('/api/v1/catalogo/1')->assertOk()->assertJsonPath('data.id', 1)
            ->assertJsonMissingPath('data.id_usuario_carga');
        $this->postJson('/api/v1/catalogo', [])->assertStatus(405);
    }

    public function test_filters_empty_results_bad_queries_and_missing_products_return_json(): void
    {
        Http::fake(['g3.test/api/v1/productos/999' => Http::response(['message' => 'No existe'], 404),
            'g3.test/api/v1/productos*' => Http::response(['data' => [$this->product()]])]);
        $this->get('/api/v1/catalogo?q=PRUEBA-1&marca=2&categoria=1')->assertOk()->assertJsonPath('meta.total', 1);
        $this->get('/api/v1/catalogo?q=zzzzzzzz')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/api/v1/catalogo?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
        $this->get('/api/v1/catalogo/999')->assertNotFound()->assertHeader('Content-Type', 'application/json');
        config()->set('services.catalog.url', null);
        $this->get('/api/v1/catalogo')->assertStatus(503)->assertHeader('Content-Type', 'application/json');
    }

    public function test_internal_mode_dispatches_the_registered_api_without_network_or_session_middleware(): void
    {
        config()->set('catalogo.transport', 'internal');
        $this->mock(StartSession::class)->shouldReceive('handle')->never();
        $this->mock(ProductoController::class)->shouldReceive('index')->once()
            ->andReturnUsing(function (Request $request) {
                $this->assertSame('activo', $request->query('estado'));
                $this->assertNull($request->header('X-User-Id'));

                return response()->json(['data' => [$this->product()]]);
            });
        $this->get('/api/v1/catalogo')->assertOk()->assertJsonPath('data.0.codigo', 'PRUEBA-1');
        Http::assertNothingSent();
    }

    public function test_internal_dispatch_preserves_the_callers_request_user_and_session(): void
    {
        config()->set('catalogo.transport', 'internal');
        $user = new User;
        $user->forceFill(['id' => 777, 'rol' => 'administrativo', 'estado' => 'activo']);
        $this->actingAs($user)->withSession(['marker' => 'sesión exterior']);
        $outerRequest = app('request');
        $outerAuth = app('auth');
        $outerStore = app('session.store');
        $sessionId = $outerStore->getId();
        $this->mock(ProductoController::class)->shouldReceive('index')->once()->andReturnUsing(function () {
            $this->assertTrue(Auth::guest());
            $this->assertNull(session('marker'));

            return response()->json(['data' => [$this->product()]]);
        });
        $response = app(InternalCatalogApi::class)->get('/api/v1/productos', ['estado' => 'activo']);
        $this->assertSame(200, $response->status(), $response->body());
        $this->assertSame($outerRequest, app('request'));
        $this->assertSame($outerAuth, app('auth'));
        $this->assertSame($outerStore, app('session.store'));
        $this->assertSame($sessionId, app('session.store')->getId());
        $this->assertSame('sesión exterior', session('marker'));
        $this->assertSame(777, Auth::id());
    }

    public function test_internal_calls_keep_api_authorization_middleware(): void
    {
        $request = Request::create('/api/v1/productos', 'GET');
        $route = app('router')->getRoutes()->match($request);
        $route->middleware(DenyCatalogApiFixture::class);
        $this->mock(ProductoController::class)->shouldNotReceive('index');
        $response = app(InternalCatalogApi::class)->get('/api/v1/productos');
        $this->assertSame(403, $response->status(), $response->body());
        $this->assertContains(DenyCatalogApiFixture::class, $route->gatherMiddleware());
        $this->assertContains(StartSession::class, $route->gatherMiddleware());
    }

    public function test_catalog_consumption_inside_a_private_g1_page_preserves_login_and_request(): void
    {
        config()->set('catalogo.transport', 'internal');
        $user = new User;
        $user->forceFill(['id' => 777, 'rol' => 'administrativo', 'estado' => 'activo',
            'current_session_id' => hash('sha256', 'sesion-privada')]);
        $this->actingAs($user)->withSession(['session_token' => 'sesion-privada', 'last_activity' => now()->timestamp]);
        $this->mock(ProductoController::class)->shouldReceive('index')->andReturn(response()->json(['data' => [$this->product()]]));
        Route::get('/verificacion-catalogo-privado', function (Request $request) {
            $id = $request->user()->id;
            $products = app(CatalogoGateway::class)->products();

            return response()->json(['actor' => Auth::id(), 'actor_antes' => $id, 'cantidad' => $products->count(),
                'misma_peticion' => $request === app('request'), 'sesion' => session('session_token')]);
        })->middleware(['web', 'auth', 'session.current']);
        $this->getJson('/verificacion-catalogo-privado')->assertOk()->assertExactJson([
            'actor' => 777, 'actor_antes' => 777, 'cantidad' => 1, 'misma_peticion' => true, 'sesion' => 'sesion-privada',
        ]);
    }

    public function test_context_is_restored_when_the_source_raises_an_exception(): void
    {
        $outer = app('request');
        $this->mock(ProductoController::class)->shouldReceive('index')->andThrow(new \RuntimeException('Error de origen'));
        $this->mock(ExceptionHandler::class)->shouldReceive('render')->once()->andReturn(response()->json(['message' => 'Error'], 500));
        $this->assertSame(500, app(InternalCatalogApi::class)->get('/api/v1/productos')->status());
        $this->assertSame($outer, app('request'));
    }

    public function test_internal_transport_rejects_urls_and_other_module_paths(): void
    {
        foreach (['https://otro.test/api/v1/productos', '/api/v1/clientes', '/api/v1/productos/../clientes', '/api/v1/productos/1/activar'] as $path) {
            try {
                app(InternalCatalogApi::class)->get($path);
                $this->fail('La ruta debía rechazarse.');
            } catch (HttpException $error) {
                $this->assertSame(503, $error->getStatusCode());
            }
        }
        Http::assertNothingSent();
    }
}

class DenyCatalogApiFixture
{
    public function handle(Request $request, Closure $next)
    {
        return response()->json(['message' => 'Origen protegido'], 403);
    }
}
