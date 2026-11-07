<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Catalogo\Services\CatalogoGateway;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\Marca;
use App\Modules\Productos\Models\Producto;
use App\Services\Gateways\ExternalCatalogGateway;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\UsesG3Database;

/**
 * Suite de integración externa de G3: valida exclusivamente todos los endpoints
 * de app/Modules/Productos/routes/api.php que consumen los Grupos 1, 2 y 4.
 */
class G3IntegracionGruposApiTest extends TestCase
{
    use DatabaseTransactions, UsesG3Database {
        UsesG3Database::beginDatabaseTransaction insteadof DatabaseTransactions;
    }

    protected User $admin;
    protected Categoria $categoria;
    protected Marca $marca;
    protected Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('usuario', 'admin')->first()
            ?? User::firstOrCreate(
                ['usuario' => 'admin'],
                [
                    'password_hash' => bcrypt('Admin1234'),
                    'nombre' => 'Usuario Administrativo Test',
                    'rol' => UserRole::ADMINISTRATIVO,
                    'estado' => UserStatus::ACTIVO,
                ]
            );

        $this->categoria = Categoria::first() ?? Categoria::create([
            'nombre' => 'Cat Integración ' . uniqid(),
            'activo' => true,
        ]);

        $this->marca = Marca::first() ?? Marca::create([
            'nombre' => 'Marca Integración ' . uniqid(),
            'activo' => true,
        ]);

        // Costo = 1000, Recargo = 45% -> Precio neto de venta = 1450.00
        $this->producto = Producto::create([
            'codigo' => 'INT-' . strtoupper(substr(uniqid(), -6)),
            'nombre' => 'Producto Integración Grupos',
            'descripcion' => 'Producto Integración Grupos x 500g',
            'precio_unitario' => 1450.00,
            'costo' => 1000.00,
            'recargo' => 45.00,
            'stock' => 40,
            'stock_minimo' => 5,
            'unidad_medida' => 'unidad',
            'formato_bulto' => 'caja',
            'unidades_por_bulto' => 12,
            'estado' => 'activo',
            'id_categoria' => $this->categoria->id_categoria,
            'id_marca' => $this->marca->id_marca,
            'id_usuario_carga' => $this->admin->id,
        ]);
    }

    /**
     * 1. Índice de integración GET /api y GET /api/v1.
     */
    public function test_indice_endpoints_integracion_g3(): void
    {
        foreach (['/api', '/api/v1'] as $uri) {
            $res = $this->getJson($uri);
            $res->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonFragment(['GET /api/productos'])
                ->assertJsonFragment(['GET /api/stock/{id}'])
                ->assertJsonFragment(['POST /api/stock/movimientos'])
                ->assertJsonFragment(['POST /api/stock/devoluciones']);
        }
    }

    /**
     * 2. GET /api/productos y GET /api/v1/productos (consumidos por G1 y G4):
     *    entrega los 24 campos comerciales con el precio calculado por el recargo
     *    y oculta costo, recargo, margen_ganancia y campos de auditoría interna.
     */
    public function test_listado_productos_cumple_contrato_g1_g2_g4_y_oculta_datos_internos(): void
    {
        foreach (['/api/productos', '/api/v1/productos'] as $baseUri) {
            $res = $this->getJson("{$baseUri}?estado=activo&buscar={$this->producto->codigo}&id_categoria={$this->categoria->id_categoria}&id_marca={$this->marca->id_marca}");
            $res->assertOk()
                ->assertJsonPath('status', 'success');

            $item = collect($res->json('data'))->firstWhere('id_producto', $this->producto->id_producto);
            $this->assertNotNull($item);

            // Campos requeridos por G1, G2 y G4
            $this->assertSame($this->producto->id_producto, $item['id']);
            $this->assertSame($this->producto->id_producto, $item['id_producto']);
            $this->assertSame($this->producto->codigo, $item['codigo']);
            $this->assertSame($this->producto->nombre, $item['nombre']);
            $this->assertSame($this->producto->descripcion, $item['descripcion']);
            $this->assertSame(1450.0, (float) $item['precio_unitario']);
            $this->assertSame(1450.0, (float) $item['precio_mayorista']);
            $this->assertSame(1450.0, (float) $item['precio_minorista']);
            $this->assertSame(1450.0, (float) $item['precioMay']);
            $this->assertSame(1450.0, (float) $item['precioMin']);
            $this->assertSame(40, (int) $item['stock']);
            $this->assertSame(40, (int) $item['stock_disponible']);
            $this->assertSame(5, (int) $item['stock_minimo']);
            $this->assertSame('caja', $item['formato_bulto']);
            $this->assertSame(12, (int) $item['unidades_por_bulto']);
            $this->assertSame('caja x 12 u.', $item['presentacion_texto']);
            $this->assertSame('activo', $item['estado']);

            // Datos confidenciales de G3 que NO deben exponerse a otros grupos
            foreach ([
                'costo',
                'recargo',
                'margen_ganancia',
                'id_ubicacion',
                'ubicacion_nombre',
                'id_unidad_activa',
                'fecha_alta',
                'fecha_modificacion',
                'fecha_desactivacion',
                'id_usuario_carga',
                'usuario_carga_nombre',
                'id_usuario_modificacion',
                'usuario_modificacion_nombre',
                'historial_precios',
            ] as $campoInterno) {
                $this->assertArrayNotHasKey($campoInterno, $item, "El campo interno '{$campoInterno}' no debe filtrarse hacia otros grupos.");
            }
        }
    }

    /**
     * 3. Consumo directo por los Gateways reales de G1 (CatalogoGateway y ExternalCatalogGateway).
     */
    public function test_gateways_de_g1_consumen_api_v1_productos_sin_errores(): void
    {
        config([
            'catalogo.transport' => 'internal',
            'services.catalog.products_path' => '/api/v1/productos',
        ]);

        $catalogoG1 = app(CatalogoGateway::class);
        $lista = $catalogoG1->products();
        $encontrado = $lista->firstWhere('id', $this->producto->id_producto);
        $this->assertNotNull($encontrado);
        $this->assertSame('1450.00', $encontrado->precio);

        $detalle = $catalogoG1->find($this->producto->id_producto);
        $this->assertSame($this->producto->id_producto, $detalle->id);
        $this->assertSame('1450.00', $detalle->precio);
    }

    /**
     * 4. Detalle de producto por ID y por código SKU (G1, G2 y G4) + validación 404.
     */
    public function test_detalle_producto_por_id_y_por_codigo_sku_y_404(): void
    {
        $resId = $this->getJson("/api/productos/{$this->producto->id_producto}");
        $resId->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id_producto', $this->producto->id_producto)
            ->assertJsonPath('data.formato_bulto', 'caja')
            ->assertJsonPath('data.unidades_por_bulto', 12)
            ->assertJsonMissingPath('data.costo')
            ->assertJsonMissingPath('data.recargo')
            ->assertJsonMissingPath('data.margen_ganancia')
            ->assertJsonMissingPath('data.historial_precios');

        $resSku = $this->getJson("/api/productos/codigo/{$this->producto->codigo}");
        $resSku->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.codigo', $this->producto->codigo)
            ->assertJsonMissingPath('data.costo')
            ->assertJsonMissingPath('data.recargo');

        $this->getJson('/api/productos/99999999')->assertNotFound();
        $this->getJson('/api/productos/codigo/SKU-INEXISTENTE-999')->assertNotFound();
    }

    /**
     * 5. Consulta de stock general y por producto (G4 HttpStockApi::disponible).
     */
    public function test_consulta_stock_general_y_por_producto_para_g4(): void
    {
        $resGeneral = $this->getJson("/api/stock?search={$this->producto->codigo}");
        $resGeneral->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.id_producto', $this->producto->id_producto)
            ->assertJsonPath('data.0.stock_disponible', 40)
            ->assertJsonMissingPath('data.0.costo');

        foreach (["/api/stock/{$this->producto->id_producto}", "/api/stock/productos/{$this->producto->id_producto}"] as $uri) {
            $resProd = $this->getJson($uri);
            $resProd->assertOk()
                ->assertJsonPath('status', 'success')
                ->assertJsonPath('disponible', 40)
                ->assertJsonPath('stock', 40)
                ->assertJsonPath('data.stock_disponible', 40)
                ->assertJsonMissingPath('data.costo');
        }

        $this->getJson('/api/stock/99999999')->assertNotFound();
    }

    /**
     * 6. Movimientos de stock en lote (G4 HttpStockApi::ajustar):
     *    descuento por venta (-N), reposición por anulación (+N) y bloqueo de stock negativo.
     */
    public function test_movimientos_stock_lote_venta_anulacion_y_bloqueo_negativo_para_g4(): void
    {
        // A. Descuento por confirmación de venta (-5)
        $resVenta = $this->postJson('/api/stock/movimientos', [
            'origen' => 'ventas',
            'venta_id' => 7001,
            'motivo' => 'Confirmación de venta G4',
            'items' => [
                ['id_producto' => $this->producto->id_producto, 'cantidad' => -5],
            ],
        ]);
        $resVenta->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.tipo', 'venta')
            ->assertJsonPath('data.0.disponible', 35);

        // B. Reposición por anulación de venta (+5)
        $resAnulacion = $this->postJson('/api/stock/movimientos', [
            'origen' => 'ventas',
            'venta_id' => 7001,
            'motivo' => 'Anulación de venta G4',
            'items' => [
                ['id_producto' => $this->producto->id_producto, 'cantidad' => 5],
            ],
        ]);
        $resAnulacion->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.tipo', 'devolucion')
            ->assertJsonPath('data.0.disponible', 40);

        // C. Rechazo por stock insuficiente (-9999)
        $resInsuficiente = $this->postJson('/api/stock/movimientos', [
            'origen' => 'ventas',
            'venta_id' => 7002,
            'motivo' => 'Intento sin stock',
            'items' => [
                ['id_producto' => $this->producto->id_producto, 'cantidad' => -9999],
            ],
        ]);
        $resInsuficiente->assertStatus(400)
            ->assertJsonPath('status', 'error');

        $this->assertSame(40, (int) $this->producto->fresh()->stock_disponible);
    }

    /**
     * 7. Descuento unitario por venta (G4: /api/stock/ventas, /api/stock/descuentos, PATCH /api/stock/{id}/descontar)
     *    y reingreso por devolución en reparto (G2: POST /api/stock/devoluciones).
     */
    public function test_descuento_unitario_ventas_g4_y_devolucion_reparto_g2(): void
    {
        // POST /api/stock/ventas (-2)
        $this->postJson('/api/stock/ventas', [
            'id_producto' => $this->producto->id_producto,
            'cantidad' => 2,
            'id_venta' => 8001,
            'motivo' => 'Venta mostrador G4',
        ])->assertOk()
            ->assertJsonPath('data.stock_disponible', 38);

        // Alias PATCH /api/stock/{id}/descontar (-1)
        $this->patchJson("/api/stock/{$this->producto->id_producto}/descontar", [
            'cantidad' => 1,
            'id_venta' => 8002,
        ])->assertOk()
            ->assertJsonPath('data.stock_disponible', 37);

        // POST /api/stock/devoluciones (+3 desde G2 Reparto/Rendiciones)
        $this->postJson('/api/stock/devoluciones', [
            'id_producto' => $this->producto->id_producto,
            'cantidad' => 3,
            'tipo_devolucion' => 'cliente',
            'motivo' => 'Devolución en reparto G2',
        ])->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.impacto_stock', '+3')
            ->assertJsonPath('data.stock_disponible', 40);
    }
}
