<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Services\CatalogoService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** G1 únicamente: sin migraciones, usuarios persistidos ni llamadas reales a G3. */
class CatalogoPublicoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.catalog.url' => 'https://g3.test', 'services.catalog.token' => null,
            'services.catalog.products_path' => '/api/v1/productos', 'services.catalog.auth_required' => false,
            'catalogo.image_hosts' => ['images.example.test']]);
    }

    private function product(int $id = 1, array $overrides = []): array
    {
        return array_replace(['id_producto' => $id, 'id' => $id, 'codigo' => 'P'.str_pad($id, 3, '0', STR_PAD_LEFT),
            'nombre' => 'Aceite de girasol 1,5 litros', 'descripcion' => 'Aceite para tu negocio',
            'estado' => 'activo', 'id_categoria' => 7, 'categoria_nombre' => 'Almacén',
            'id_marca' => 4, 'marca_nombre' => 'Marca de prueba', 'precio_unitario' => '1234.56',
            'stock_disponible' => 25, 'stock_minimo' => 5, 'unidad_medida' => 'Caja x 12',
            'id_usuario_carga' => 100, 'usuario_carga_nombre' => 'PERSONA_INTERNA_NO_PUBLICAR'], $overrides);
    }

    private function products(array $rows): void
    {
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['g3.test/api/v1/productos*' => Http::response(['status' => 'success', 'data' => $rows])]);
    }

    public function test_catalog_is_public_and_never_reads_the_session_or_database(): void
    {
        $this->withoutExceptionHandling();
        config(['session.driver' => 'database', 'session.connection' => 'not-a-connection']);
        DB::shouldReceive('connection')->never();
        $this->products([$this->product()]);
        $this->get('/catalogo')->assertOk()->assertSeeText('Aceite de girasol 1,5 litros')->assertSeeText('$ 1.234,56')
            ->assertSeeText('Disponible')->assertDontSee('PERSONA_INTERNA_NO_PUBLICAR')->assertDontSee('csrf-token');
        Http::assertSent(fn ($r) => $r->method() === 'GET' && $r['estado'] === 'activo' && ! $r->hasHeader('X-User-Id'));
    }

    public function test_absent_source_is_503_with_public_interface_and_no_request(): void
    {
        config(['services.catalog.url' => null]);
        $this->get('/catalogo')->assertStatus(503)->assertSeeText('No pudimos consultar el catálogo')->assertDontSee('SQLSTATE');
        Http::assertNothingSent();
    }

    public function test_empty_source_is_different_from_connection_failure(): void
    {
        $this->products([]);
        $this->get('/catalogo')->assertOk()->assertSeeText('todavía no tiene productos publicados');
    }

    public function test_unreachable_source_returns_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('private network detail'));
        $this->get('/catalogo')->assertStatus(503)->assertDontSee('private network detail');
    }

    public function test_malformed_source_and_redirects_are_not_treated_as_empty(): void
    {
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['*' => Http::response(['data' => 'wrong'])]);
        $this->get('/catalogo')->assertStatus(502)->assertSeeText('No pudimos consultar');
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['*' => Http::response('', 302, ['Location' => 'https://other.test'])]);
        $this->get('/catalogo')->assertStatus(502);
    }

    public function test_unknown_stock_is_not_shown_as_unavailable_or_as_confirmed_stock(): void
    {
        $this->products([$this->product(1, ['stock_disponible' => null, 'stock' => 999])]);
        $this->get('/catalogo')->assertOk()->assertSeeText('Consultar disponibilidad')->assertDontSee('cat-stock-sin-stock')->assertDontSeeText('999');
    }

    public function test_zero_and_low_stock_have_distinct_states(): void
    {
        $this->products([$this->product(1, ['stock_disponible' => 0]), $this->product(2, ['stock_disponible' => 2])]);
        $this->get('/catalogo')->assertOk()->assertSeeText('Sin stock')->assertSeeText('Últimas unidades');
        $this->get('/catalogo?disponibilidad=disponible')->assertOk()->assertSeeText('1 producto encontrado');
    }

    public function test_inactive_products_are_hidden_even_when_the_source_ignores_our_filter(): void
    {
        $this->products([$this->product(1, ['estado' => 'inactivo', 'nombre' => 'INACTIVO_NO_PUBLICAR']), $this->product(2)]);
        $this->get('/catalogo')->assertOk()->assertDontSee('INACTIVO_NO_PUBLICAR')->assertSeeText('1 producto encontrado');
    }

    public function test_valid_name_and_null_description_are_accepted(): void
    {
        $this->products([$this->product(1, ['descripcion' => null])]);
        $this->get('/catalogo')->assertOk()->assertSeeText('Aceite de girasol');
    }

    public function test_filters_combine_category_brand_availability_and_search(): void
    {
        $this->products([$this->product(), $this->product(2, ['id_categoria' => 9, 'categoria_nombre' => 'Bebidas']),
            $this->product(3, ['stock_disponible' => 0]), $this->product(4, ['id_marca' => 8, 'marca_nombre' => 'Otra marca'])]);
        $this->get('/catalogo?categoria=7&marca=4&disponibilidad=disponible&q=aceite')->assertOk()->assertSeeText('1 producto encontrado');
    }

    public function test_search_normalizes_accents_decimal_separators_and_liter_units(): void
    {
        $this->products([$this->product()]);
        $this->get('/catalogo?q=ACEITE+1.5L')->assertOk()->assertSeeText('1 producto encontrado');
        $this->get('/catalogo?q=almacen')->assertOk()->assertSeeText('1 producto encontrado');
        $this->get('/catalogo?q=P001')->assertOk()->assertSeeText('1 producto encontrado');
    }

    public function test_small_spelling_mistakes_are_explicitly_marked_as_approximate(): void
    {
        $this->products([$this->product()]);
        $this->get('/catalogo?q=aceitw')->assertOk()->assertSeeText('resultados con nombres similares')->assertSeeText('1 producto encontrado');
    }

    public function test_missing_price_and_description_do_not_invent_commercial_information(): void
    {
        $this->products([$this->product(1, ['precio_unitario' => null, 'descripcion' => null, 'unidad_medida' => null])]);
        $this->get('/catalogo')->assertOk()->assertSeeText('Consultar precio')->assertSeeText('Presentación a consultar');
    }

    public function test_browser_payload_is_escaped_and_images_require_an_approved_host(): void
    {
        $this->products([$this->product(1, ['nombre' => '<script>EVIL()</script>', 'categoria_nombre' => '<img src=x onerror=EVIL()>', 'imagen_url' => 'javascript:EVIL()'])]);
        $this->get('/catalogo')->assertOk()->assertDontSee('<script>EVIL()</script>', false)->assertDontSee('src="javascript:', false)->assertSee('&lt;script&gt;EVIL()', false);
        $this->products([$this->product(1, ['imagen_url' => 'https://images.example.test/product.jpg'])]);
        $this->get('/catalogo')->assertOk()->assertSee('https://images.example.test/product.jpg', false);
    }

    public function test_api_pages_are_read_completely_without_following_foreign_links(): void
    {
        Http::fake(function ($r) {
            $page = (int) $r['page'];

            return Http::response(['data' => [$this->product($page)], 'meta' => ['current_page' => $page, 'last_page' => 2, 'total' => 2], 'links' => ['next' => 'https://evil.test']]);
        });
        $this->get('/catalogo')->assertOk()->assertSeeText('2 productos encontrados');
        Http::assertSentCount(2);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'evil.test'));
    }

    public function test_pagination_keeps_filters_and_places_unknown_prices_last(): void
    {
        $rows = array_map(fn ($i) => $this->product($i, ['nombre' => 'Producto '.$i, 'precio_unitario' => $i]), range(1, 15));
        $rows[] = $this->product(16, ['nombre' => 'Sin precio', 'precio_unitario' => null]);
        $this->products($rows);
        $this->get('/catalogo?categoria=7&orden=precio-desc&page=2')->assertOk()->assertSeeText('Página 2 de 2')->assertSeeText('Sin precio');
        $this->assertCount(12, app(CatalogoService::class)->browse([])['products']);
    }

    public function test_invalid_filters_are_422_without_contacting_the_provider(): void
    {
        $this->get('/catalogo?q[]=x')->assertStatus(422)->assertSeeText('La búsqueda no es válida');
        $this->get('/catalogo/datos?orden=sql')->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_detail_is_public_uses_the_detail_api_and_omits_internal_data(): void
    {
        Http::fake(['g3.test/api/v1/productos/1' => Http::response(['data' => $this->product(1, ['formato_bulto' => 'Caja', 'unidades_por_bulto' => 12])]),
            'g3.test/api/v1/productos*' => Http::response(['data' => [$this->product(2)]])]);
        $this->get('/catalogo/1')->assertOk()->assertSeeText('Caja x 12 u.')->assertSeeText('También en esta categoría')
            ->assertDontSee('PERSONA_INTERNA_NO_PUBLICAR')->assertDontSee('id_usuario_carga');
    }

    public function test_inactive_missing_and_mismatched_detail_ids_are_rejected(): void
    {
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['*' => Http::response([], 404)]);
        $this->get('/catalogo/1')->assertNotFound()->assertSeeText('no está publicado');
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['*' => Http::response(['data' => $this->product(1, ['estado' => 'inactivo'])])]);
        $this->get('/catalogo/1')->assertNotFound();
        Http::swap((new Factory)->preventStrayRequests());
        Http::fake(['*' => Http::response(['data' => $this->product(2)])]);
        $this->get('/catalogo/1')->assertStatus(502);
    }

    public function test_data_endpoint_is_public_sanitized_and_not_cacheable(): void
    {
        $this->products([$this->product()]);
        $response = $this->getJson('/catalogo/datos')->assertOk()->assertJsonStructure(['html', 'categories', 'brands', 'checked_at']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('PERSONA_INTERNA_NO_PUBLICAR', $response->getContent());
        Http::assertSent(fn ($r) => $r->method() === 'GET');
    }

    public function test_no_catalog_mutations_are_exposed(): void
    {
        $this->post('/catalogo')->assertStatus(405);
        $this->put('/catalogo/1')->assertStatus(405);
        $this->delete('/catalogo/1')->assertStatus(405);
        Http::assertNothingSent();
    }

    public function test_duplicate_product_ids_are_not_published(): void
    {
        $this->products([$this->product(), $this->product()]);
        $this->get('/catalogo')->assertStatus(502);
    }
}
