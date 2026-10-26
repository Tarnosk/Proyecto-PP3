<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Modules\Productos\Models\Producto;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\Marca;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Stock\Models\Ubicacion;
use App\Modules\Stock\Models\Lote;
use App\Modules\Stock\Models\UnidadMedida;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Compras\Models\Compra;
use App\Modules\PedidosDeCompra\Models\OrdenCompra;

class UserStoriesFrontendTest extends TestCase
{
    use DatabaseTransactions, \Tests\UsesG3Database {
        \Tests\UsesG3Database::beginDatabaseTransaction insteadof DatabaseTransactions;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $admin = \App\Modules\Login\Models\User::where('usuario', 'admin')->first()
            ?? \App\Modules\Login\Models\User::firstOrCreate(
                ['usuario' => 'admin'],
                [
                    'password_hash' => \Illuminate\Support\Facades\Hash::make('Admin1234'),
                    'nombre' => 'Usuario Administrativo',
                    'rol' => \App\Modules\Login\Enums\UserRole::ADMINISTRATIVO,
                    'estado' => \App\Modules\Login\Enums\UserStatus::ACTIVO,
                ]
            );
        $this->signInAs($admin);
    }

    /**
     * HU Verificación de Infraestructura Frontend Blade:
     * Comprueba que todas las vistas migradas a Blade respondan HTTP 200 y carguen el layout con Claymorfismo.
     */
    public function test_01_all_blade_frontend_views_render_successfully(): void
    {
        // 1. Dashboard Principal (G1) y Panel G3
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertViewIs('dashboard');
        $resDash = $this->get('/panel-g3');
        $resDash->assertOk();
        $resDash->assertViewIs('dashboard-g3');
        $resDash->assertSee('Distribuidora PyB');
        $resDash->assertSee('Stock e Inventario');
        $resDash->assertSee('Compras y Órdenes');
        $resDash->assertSee('Proveedores');

        // 2. Módulo Inventario (Productos, Stock, Lotes, Movimientos, Conteo, Devoluciones)
        $resInv = $this->get('/inventario');
        $resInv->assertOk();
        $resInv->assertViewIs('inventario.index');
        $resInv->assertSee('Inventario y Stock');
        $resInv->assertSee('Catálogo de Productos');
        $resInv->assertSee('Stock y Alertas');
        $resInv->assertSee('Movimientos');
        $resInv->assertSee('Conteo Físico');
        $resInv->assertSee('Devoluciones');

        // 3. Módulo Compras y Órdenes
        $resComp = $this->get('/compras');
        $resComp->assertOk();
        $resComp->assertViewIs('compras.index');
        $resComp->assertSee('Compras y Órdenes de Compra');
        $resComp->assertSee('Compras Realizadas');
        $resComp->assertSee('Órdenes de Compra');
        $resComp->assertSee('Sugerencias de Reposición');

        // 4. Módulo Proveedores
        $resProv = $this->get('/proveedores');
        $resProv->assertOk();
        $resProv->assertViewIs('proveedores.index');
        $resProv->assertSee('Gestión de Proveedores');
        $resProv->assertSee('Nuevo Proveedor');
        $resProv->assertSee('Plazo de Entrega Promedio');

        // 5. Subvistas Especializadas (Vencimientos S11 y Sugerencias PC07)
        $this->get('/vencimientos')->assertRedirect('/inventario');
        $this->get('/sugerencias-reposicion')->assertOk();
        $this->get('/dashboard-reposicion')->assertOk();
    }

    // =========================================================================
    // MÓDULO PRODUCTOS (P01 a P08)
    // =========================================================================

    /**
     * P01, P04, P05 - Registrar, Modificar y Buscar Productos en el Front y API.
     */
    public function test_p01_p04_p05_productos_frontend_elements_and_lifecycle(): void
    {
        // 1. Verificar elementos UI en la vista Blade de Inventario
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('modalProducto');
        $viewRes->assertSee('prodCodigo');
        $viewRes->assertSee('prodNombre');
        $viewRes->assertSee('prodDescripcion');
        $viewRes->assertSee('prodCategoria');
        $viewRes->assertSee('prodMarca');
        $viewRes->assertSee('prodPrecioUnitario');
        $viewRes->assertSee('prodStockMin');
        $viewRes->assertSee('filtroSearch');
        $viewRes->assertSee('filtroCategoria');
        $viewRes->assertSee('filtroMarca');
        $viewRes->assertSee('filtroEstado');
        $viewRes->assertSee('btnVerHistorialDesdeModalProd');
        $viewRes->assertSee('historialAuditoriaBody');
        $viewRes->assertSee('contenedorMovimientosAuditoria');

        // 2. Obtener categoría y marca existentes
        $categoria = Categoria::first();
        $marca = Marca::first();
        $codigoTest = 'TEST-P01-' . uniqid();

        // P01: Crear Producto vía API (consumida por modalProducto)
        $storeRes = $this->postJson('/api/productos', [
            'codigo' => $codigoTest,
            'nombre' => 'Producto Test Automatizado P01',
            'descripcion' => 'Descripción detallada P01',
            'precio_unitario' => 1500.50,
            'precio_mayorista' => 1350.00,
            'id_categoria' => $categoria->id_categoria,
            'id_marca' => $marca->id_marca,
            'stock_disponible' => 20,
            'stock_minimo' => 5,
        ]);
        $storeRes->assertStatus(201);
        $prodId = $storeRes->json('data.id_producto');
        $this->assertNotNull($prodId);

        // Validación P01: Código duplicado debe ser rechazado
        $dupRes = $this->postJson('/api/productos', [
            'codigo' => $codigoTest,
            'nombre' => 'Producto Duplicado',
            'descripcion' => 'Duplicado',
            'precio_unitario' => 1000,
            'id_categoria' => $categoria->id_categoria,
            'id_marca' => $marca->id_marca,
        ]);
        $dupRes->assertStatus(400);

        // P05: Búsqueda de Producto por código o nombre
        $searchRes = $this->getJson("/api/productos?search={$codigoTest}");
        $searchRes->assertOk();
        $this->assertNotEmpty($searchRes->json('data'));
        $this->assertEquals($codigoTest, $searchRes->json('data.0.codigo'));

        // P04: Modificar Producto
        $updateRes = $this->putJson("/api/productos/{$prodId}", [
            'codigo' => $codigoTest,
            'nombre' => 'Producto Test Modificado P04',
            'descripcion' => 'Descripción modificada P04',
            'precio_unitario' => 1800.00,
            'precio_minorista' => 1800.00,
            'precio_mayorista' => 1600.00,
            'id_categoria' => $categoria->id_categoria,
            'id_marca' => $marca->id_marca,
            'stock_minimo' => 8,
        ]);
        $updateRes->assertOk();
        $this->assertEquals('Producto Test Modificado P04', $updateRes->json('data.nombre'));
        $this->assertEquals(1800.00, (float)$updateRes->json('data.precio_unitario'));

        // P04: Historial de modificaciones y auditoría
        $auditProdRes = $this->getJson("/api/productos/{$prodId}/auditoria");
        $auditProdRes->assertOk();
        $this->assertTrue($auditProdRes->json('status') === 'success');
        $this->assertNotEmpty($auditProdRes->json('data'));

        $auditGlobalRes = $this->getJson('/api/productos/auditoria');
        $auditGlobalRes->assertOk();
        $this->assertTrue($auditGlobalRes->json('status') === 'success');
        $this->assertNotEmpty($auditGlobalRes->json('data'));
    }

    /**
     * P02, P03 - Administrar Categorías y Marcas.
     */
    public function test_p02_p03_categorias_y_marcas_frontend_elements_and_api(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('modalCategoria');
        $viewRes->assertSee('modalMarca');
        $viewRes->assertSee('catNombre');
        $viewRes->assertSee('marcaNombre');
        $viewRes->assertSee('abrirModalCategorias()', false);
        $viewRes->assertSee('abrirModalMarcas()', false);

        // P02: Crear Categoría
        $catNombre = 'Cat Test ' . uniqid();
        $catRes = $this->postJson('/api/categorias', [
            'nombre' => $catNombre,
            'descripcion' => 'Categoría de prueba para test suite',
        ]);
        $catRes->assertStatus(201);
        $catId = $catRes->json('data.id_categoria');
        $this->assertEquals($catNombre, $catRes->json('data.nombre'));

        // P02: Modificar Categoría
        $catModNombre = $catNombre . ' Mod';
        $updateCatRes = $this->putJson("/api/categorias/{$catId}", [
            'nombre' => $catModNombre,
        ]);
        $updateCatRes->assertOk();
        $this->assertEquals($catModNombre, $updateCatRes->json('data.nombre'));

        // P02: Eliminar Categoría no utilizada
        $deleteCatRes = $this->deleteJson("/api/categorias/{$catId}");
        $deleteCatRes->assertOk();

        // P02: Impedir eliminar categoría asociada a productos
        $categoriaEnUso = Categoria::has('productos')->first();
        if ($categoriaEnUso) {
            $delUsedCatRes = $this->deleteJson("/api/categorias/{$categoriaEnUso->id_categoria}");
            $delUsedCatRes->assertStatus(400);
            $this->assertStringContainsString('no se puede eliminar', strtolower($delUsedCatRes->json('message')));
        }

        // Listar categorías
        $listCatRes = $this->getJson('/api/categorias');
        $listCatRes->assertOk();

        // P03: Crear Marca
        $marcaNombre = 'Marca Test ' . uniqid();
        $marcaRes = $this->postJson('/api/marcas', [
            'nombre' => $marcaNombre,
            'descripcion' => 'Marca de prueba para test suite',
        ]);
        $marcaRes->assertStatus(201);
        $marcaId = $marcaRes->json('data.id_marca');
        $this->assertEquals($marcaNombre, $marcaRes->json('data.nombre'));

        // P03: Modificar Marca
        $marcaModNombre = $marcaNombre . ' Mod';
        $updateMarcaRes = $this->putJson("/api/marcas/{$marcaId}", [
            'nombre' => $marcaModNombre,
        ]);
        $updateMarcaRes->assertOk();
        $this->assertEquals($marcaModNombre, $updateMarcaRes->json('data.nombre'));

        // P03: Eliminar Marca no utilizada
        $deleteMarcaRes = $this->deleteJson("/api/marcas/{$marcaId}");
        $deleteMarcaRes->assertOk();

        // P03: Impedir eliminar marca asociada a productos
        $marcaEnUso = Marca::has('productos')->first();
        if ($marcaEnUso) {
            $delUsedMarcaRes = $this->deleteJson("/api/marcas/{$marcaEnUso->id_marca}");
            $delUsedMarcaRes->assertStatus(400);
            $this->assertStringContainsString('no se puede eliminar', strtolower($delUsedMarcaRes->json('message')));
        }

        // Listar marcas
        $listMarcaRes = $this->getJson('/api/marcas');
        $listMarcaRes->assertOk();
    }

    /**
     * P06, P07 - Desactivar y Activar Producto (Borrado Lógico) y Aumento Masivo (P07).
     */
    public function test_p06_p07_desactivar_activar_borrado_logico_y_aumento_masivo(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('modalAumento');
        $viewRes->assertSee('aumentoCriterio');
        $viewRes->assertSee('aumentoPorcentaje');
        $viewRes->assertSee('cambiarEstadoProducto');

        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($producto);
        $id = $producto->id_producto;

        // P06: Desactivar producto
        $desactRes = $this->patchJson("/api/productos/{$id}/desactivar");
        $desactRes->assertOk();
        $this->assertEquals('inactivo', $desactRes->json('data.estado'));

        // Reactivar producto
        $actRes = $this->patchJson("/api/productos/{$id}/activar");
        $actRes->assertOk();
        $this->assertEquals('activo', $actRes->json('data.estado'));

        // P07: Aumento Masivo de Precios (regla de redondeo predeterminada)
        $aumentoRes = $this->postJson('/api/productos/aumento-masivo', [
            'tipo_criterio' => 'todos',
            'porcentaje' => 10.0,
            'confirmar' => true,
        ]);
        $aumentoRes->assertOk();
        $this->assertTrue($aumentoRes->json('status') === 'success');
        $this->assertSame('sin_redondeo', $aumentoRes->json('data.regla_redondeo'));
    }

    public function test_p07_aplica_y_registra_reglas_de_redondeo_configurables(): void
    {
        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($producto);

        $casos = [
            ['regla' => 'sin_redondeo', 'precio_base' => 100.13, 'precio_esperado' => 110.14],
            ['regla' => 'peso_mas_cercano', 'precio_base' => 100.13, 'precio_esperado' => 110.0],
            ['regla' => 'peso_superior', 'precio_base' => 100.13, 'precio_esperado' => 111.0],
            ['regla' => 'terminar_90', 'precio_base' => 100.13, 'precio_esperado' => 110.90],
            ['regla' => 'terminar_99', 'precio_base' => 100.13, 'precio_esperado' => 110.99],
            ['regla' => 'terminar_90', 'precio_base' => 100.87, 'precio_esperado' => 111.90],
        ];

        foreach ($casos as $caso) {
            $producto->refresh()->update(['precio_unitario' => $caso['precio_base']]);

            $respuesta = $this->postJson('/api/productos/aumento-masivo', [
                'ids' => [$producto->id_producto],
                'porcentaje' => 10,
                'regla_redondeo' => $caso['regla'],
            ]);

            $respuesta->assertOk()
                ->assertJsonPath('data.regla_redondeo', $caso['regla']);
            $this->assertEquals(
                $caso['precio_esperado'],
                (float) $respuesta->json('data.productos.0.precio_nuevo'),
                "La regla {$caso['regla']} debe calcular el precio esperado."
            );

            $historial = \App\Modules\Productos\Models\HistorialPrecio::where('id_producto', $producto->id_producto)
                ->where('origen', 'aumento_masivo')
                ->latest('id_historial')
                ->first();
            $this->assertNotNull($historial);
            $this->assertSame($caso['regla'], $historial->regla_redondeo);
        }

        $respuestaInvalida = $this->postJson('/api/productos/aumento-masivo', [
            'ids' => [$producto->id_producto],
            'porcentaje' => 10,
            'regla_redondeo' => 'regla_invalida',
        ]);
        $respuestaInvalida->assertStatus(400);
    }

    /**
     * P08 - Consultar Historial de Precios y Aumentos Masivos.
     */
    public function test_p08_historial_precios_consulta(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('modalHistorial');
        $viewRes->assertSee('historialPreciosBody');
        $viewRes->assertSee('abrirHistorialPreciosGlobal()', false);
        $viewRes->assertSee('contenedorHistorialPrecios');
        $viewRes->assertSee('tablaMovimientosPreciosBody');
        $viewRes->assertSee('historialFiltroProducto');
        $viewRes->assertSee('historialFiltroOrigen');

        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($producto);

        // Aplicar aumento masivo específico para verificar que se registra en el historial
        $aumentoPct = 12.5;
        $aumentoRes = $this->postJson('/api/productos/aumento-masivo', [
            'ids' => [$producto->id_producto],
            'porcentaje' => $aumentoPct,
            'confirmar' => true,
        ]);
        $aumentoRes->assertOk();

        // 1. Historial específico del producto (P08)
        $histRes = $this->getJson("/api/productos/{$producto->id_producto}/historial");
        $histRes->assertOk();
        $this->assertTrue($histRes->json('status') === 'success');
        $this->assertIsArray($histRes->json('data'));
        $ultimoRegistro = $histRes->json('data.0');
        $this->assertNotNull($ultimoRegistro);
        $this->assertEquals('aumento_masivo', $ultimoRegistro['origen']);
        $this->assertEquals($aumentoPct, (float) $ultimoRegistro['porcentaje_aumento']);

        // 2. Historial global de precios (P08 / P07)
        $globalRes = $this->getJson('/api/productos/historial');
        $globalRes->assertOk();
        $this->assertTrue($globalRes->json('status') === 'success');
        $this->assertIsArray($globalRes->json('data'));
        $this->assertNotEmpty($globalRes->json('data'));

        // Filtrar por origen aumento_masivo
        $filtroMasivoRes = $this->getJson('/api/productos/historial?origen=aumento_masivo');
        $filtroMasivoRes->assertOk();
        $this->assertTrue($filtroMasivoRes->json('status') === 'success');
        $this->assertNotEmpty($filtroMasivoRes->json('data'));
        $this->assertEquals('aumento_masivo', $filtroMasivoRes->json('data.0.origen'));
    }

    // =========================================================================
    // MÓDULO STOCK (S01 a S16)
    // =========================================================================

    /**
     * S01, S02 - Consulta de Stock y Disponibilidad para Venta.
     */
    public function test_s01_s02_consulta_stock_y_disponibilidad_venta(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('tabBtn-stock');
        $viewRes->assertSee('pestana-stock');
        $viewRes->assertSee('Stock y Alertas');

        // S01: Consulta general de stock
        $stockRes = $this->getJson('/api/stock');
        $stockRes->assertOk();
        $this->assertIsArray($stockRes->json('data'));

        // S02: Disponibilidad para venta
        $producto = Producto::where('estado', 'activo')->first();
        if ($producto) {
            $dispRes = $this->getJson("/api/stock/{$producto->id_producto}/disponibilidad");
            $dispRes->assertOk();
            $this->assertArrayHasKey('stock_disponible', $dispRes->json('data'));
        }
    }

    /**
     * S03 - Registro de Ingreso de Mercadería en Stock General.
     */
    public function test_s03_s09_ingreso_mercaderia_con_lotes_y_vencimientos(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('modalIngreso');
        $viewRes->assertSee('ingresoProveedorSelect');
        $viewRes->assertSee('ingresoProductoSelect');
        $viewRes->assertSee('ingresoCantidad');

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($proveedor);
        $this->assertNotNull($producto);

        $stockAntes1 = (int) $producto->fresh()->stock;

        $ingresoRes = $this->postJson('/api/stock/ingresos', [
            'id_proveedor' => $proveedor->id_proveedor,
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 15,
                ]
            ],
            'observaciones' => 'Ingreso de prueba test S03',
        ]);
        $ingresoRes->assertStatus(201);
        $this->assertTrue($ingresoRes->json('status') === 'success');
        $this->assertEquals($stockAntes1 + 15, (int) $producto->fresh()->stock);

        // Verificar que el producto quedó asociado automáticamente al proveedor
        $this->assertTrue(ProductoProveedor::where('id_producto', $producto->id_producto)
            ->where('id_proveedor', $proveedor->id_proveedor)
            ->where('activo', true)
            ->exists());

        // Test flat payload ingreso directo (compatibilidad formulario web)
        $stockAntes2 = (int) $producto->fresh()->stock;
        $flatRes = $this->postJson('/api/stock/ingresos', [
            'id_proveedor' => $proveedor->id_proveedor,
            'id_producto' => $producto->id_producto,
            'cantidad' => 10,
        ]);
        $flatRes->assertStatus(201);
        $this->assertTrue($flatRes->json('status') === 'success');
        $this->assertEquals($stockAntes2 + 10, (int) $producto->fresh()->stock);

        // Verificar que el movimiento quedó registrado en el Kardex (S12) con id_lote nulo
        $movsRes = $this->getJson('/api/stock/movimientos');
        $movsRes->assertOk();
        $this->assertNotEmpty($movsRes->json('data'));

        $movIngreso = MovimientoStock::where('id_producto', $producto->id_producto)
            ->where('tipo', 'ingreso')
            ->latest('id_movimiento')
            ->first();
        $this->assertNotNull($movIngreso);
        $this->assertNull($movIngreso->id_lote);
    }

    /**
     * S04 - Actualización Automática por Venta (Descuento de Stock).
     */
    public function test_s04_actualizacion_automatica_por_venta(): void
    {
        $producto = Producto::where('estado', 'activo')->first();
        if (!$producto) {
            $producto = Producto::first();
        }
        $producto->stock = 25;
        $producto->estado = 'activo';
        $producto->save();

        $stockInicial = $producto->stock;

        $ventaRes = $this->postJson('/api/stock/ventas', [
            'id_producto' => $producto->id_producto,
            'cantidad' => 2,
            'id_venta' => null,
            'motivo' => 'Venta test S04',
        ]);
        $ventaRes->assertStatus(200);

        $producto->refresh();
        $this->assertEquals($stockInicial - 2, $producto->stock);
    }

    /**
     * S05 - Registro de Devoluciones en Stock General.
     */
    public function test_s05_registro_devoluciones(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertDontSee('tabBtn-devoluciones');
        $viewRes->assertSee('modalDevolucion');
        $viewRes->assertSee('abrirModalDevolucion');
        $viewRes->assertSee('modalDevTipo');
        $viewRes->assertSee('modalDevProductoSelect');
        $viewRes->assertSee('modalDevCantidad');
        $viewRes->assertSee('modalDevMotivo');

        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($producto);

        // 1. Devolución de cliente: incrementa stock del producto
        $stockProdAntes = (int) $producto->fresh()->stock_disponible;
        $devRes = $this->postJson('/api/stock/devoluciones', [
            'id_producto' => $producto->id_producto,
            'tipo_devolucion' => 'cliente',
            'cantidad' => 3,
            'motivo' => 'Producto con empaque dañado devuelto por cliente',
        ]);
        $devRes->assertStatus(201);
        $this->assertTrue($devRes->json('status') === 'success');
        $this->assertEquals($stockProdAntes + 3, (int) $producto->fresh()->stock_disponible);

        // Verificar que el movimiento en Kardex se registró con id_lote null
        $mov = MovimientoStock::where('id_producto', $producto->id_producto)
            ->where('tipo', 'devolucion')
            ->latest('id_movimiento')
            ->first();
        $this->assertNotNull($mov);
        $this->assertNull($mov->id_lote);

        // 2. Devolución a proveedor: decrementa stock del producto
        $stockProdAntesProv = (int) $producto->fresh()->stock_disponible;
        $devProvRes = $this->postJson('/api/stock/devoluciones', [
            'id_producto' => $producto->id_producto,
            'tipo_devolucion' => 'proveedor',
            'cantidad' => 2,
            'motivo' => 'Devolución a proveedor por falla de fábrica',
        ]);
        $devProvRes->assertStatus(201);
        $this->assertEquals('success', $devProvRes->json('status'));
        $this->assertEquals($stockProdAntesProv - 2, (int) $producto->fresh()->stock_disponible);

        // 3. Intentar devolver a proveedor más unidades que el stock disponible -> Error 400
        $devExcesoRes = $this->postJson('/api/stock/devoluciones', [
            'id_producto' => $producto->id_producto,
            'tipo_devolucion' => 'proveedor',
            'cantidad' => 99999,
            'motivo' => 'Intento excedente',
        ]);
        $devExcesoRes->assertStatus(400);

        // 4. Devolución en unidades individuales con id_unidad = 0 o null (integridad FK)
        $devUnidadCeroRes = $this->postJson('/api/stock/devoluciones', [
            'id_producto' => $producto->id_producto,
            'tipo_devolucion' => 'proveedor',
            'cantidad' => 1,
            'id_unidad' => 0,
            'motivo' => 'Devolución individual con id_unidad 0',
        ]);
        $devUnidadCeroRes->assertStatus(201);
        $this->assertEquals('success', $devUnidadCeroRes->json('status'));
        $this->assertNull($devUnidadCeroRes->json('data.id_unidad'));

        $devUnidadNullRes = $this->postJson('/api/stock/devoluciones', [
            'id_producto' => $producto->id_producto,
            'tipo_devolucion' => 'cliente',
            'cantidad' => 1,
            'id_unidad' => null,
            'motivo' => 'Devolución individual con id_unidad null',
        ]);
        $devUnidadNullRes->assertStatus(201);
        $this->assertEquals('success', $devUnidadNullRes->json('status'));
        $this->assertNull($devUnidadNullRes->json('data.id_unidad'));
    }

    /**
     * S06 - Ajustes Manuales de Inventario en Stock General.
     */
    public function test_s06_ajustes_manuales_de_inventario(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('modalAjuste');
        $viewRes->assertSee('ajusteProductoSelect');
        $viewRes->assertSee('ajusteTipo');
        $viewRes->assertSee('ajusteCantidad');

        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($producto);
        $producto->stock = 20;
        $producto->save();

        // 1. Ajuste manual de DISMINUCIÓN (-)
        $ajusteDisRes = $this->postJson('/api/stock/ajustes', [
            'id_producto' => $producto->id_producto,
            'tipo' => 'disminuir',
            'cantidad' => 3,
            'motivo' => 'Merma de depósito',
        ]);
        $ajusteDisRes->assertStatus(201)
            ->assertJsonPath('data.id_lote', null)
            ->assertJsonPath('data.stock_disponible', 17);
        $this->assertSame(17, (int) $producto->fresh()->stock_disponible);

        $movimientoDis = MovimientoStock::where('id_producto', $producto->id_producto)
            ->where('tipo', 'ajuste')
            ->latest('id_movimiento')
            ->first();
        $this->assertNotNull($movimientoDis);
        $this->assertNull($movimientoDis->id_lote);

        // 2. Intentar disminuir más unidades que las disponibles en stock -> Error 400
        $ajusteExcesoRes = $this->postJson('/api/stock/ajustes', [
            'id_producto' => $producto->id_producto,
            'tipo' => 'disminuir',
            'cantidad' => 9999,
            'motivo' => 'Intento de merma excedente',
        ]);
        $ajusteExcesoRes->assertStatus(400);
        $this->assertSame(17, (int) $producto->fresh()->stock_disponible);

        // 3. Ajuste manual de AUMENTO (+)
        $ajusteAumRes = $this->postJson('/api/stock/ajustes', [
            'id_producto' => $producto->id_producto,
            'tipo' => 'aumentar',
            'cantidad' => 5,
            'motivo' => 'Recuperación de mercadería extraviada',
        ]);
        $ajusteAumRes->assertStatus(201)
            ->assertJsonPath('data.id_lote', null)
            ->assertJsonPath('data.stock_disponible', 22);
        $this->assertSame(22, (int) $producto->fresh()->stock_disponible);

        // 4. Fijar cantidad exacta del stock ('fijar')
        $nuevoValor = 30;
        $ajusteFijarRes = $this->postJson('/api/stock/ajustes', [
            'id_producto' => $producto->id_producto,
            'tipo' => 'fijar',
            'cantidad' => $nuevoValor,
            'motivo' => 'Conteo físico exacto',
        ]);
        $ajusteFijarRes->assertStatus(201)
            ->assertJsonPath('data.id_lote', null)
            ->assertJsonPath('data.stock_disponible', $nuevoValor);
        $this->assertSame($nuevoValor, (int) $producto->fresh()->stock_disponible);
    }

    /**
     * S07, S15, S16 - Alertas de Stock Mínimo, Filtrado por Nivel de Stock y Semáforo Visual.
     */
    public function test_s07_s15_s16_alertas_stock_minimo_y_semaforo_visual(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('kpiCriticosCat');
        $viewRes->assertSee('prodStockMin');
        $viewRes->assertSee('filtroNivelStock');
        $viewRes->assertSee('filtroStockMinCant');
        $viewRes->assertSee('filtroStockMaxCant');
        $viewRes->assertSee('filtroStockNivel');
        $viewRes->assertSee('btnSubtabStockTodos');
        $viewRes->assertSee('btnSubtabStockAlertas');
        $viewRes->assertSee('contenedorStockCompleto');

        // S07: Endpoint de alertas
        $alertasRes = $this->getJson('/api/stock/alertas');
        $alertasRes->assertOk();
        $this->assertTrue($alertasRes->json('status') === 'success');
    }

    /**
     * S08 - Gestión de Unidades, Equivalencias y Visualización en Sistema.
     */
    public function test_s08_unidades_medida_y_conversion(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('modalUnidades');
        $viewRes->assertSee('unidadModalNombre');
        $viewRes->assertSee('unidadModalEquiv');
        $viewRes->assertSee('unidadModalEsBase');
        $viewRes->assertSee('seleccionarUnidadMedidaProducto');
        $viewRes->assertSee('restablecerUnidadesIndividuales');
        $viewRes->assertDontSee('calcUnidadOrigen');
        $viewRes->assertDontSee('calcUnidadDestino');
        $viewRes->assertDontSee('calcularConversionUnidades');

        $producto = Producto::first();
        $this->assertNotNull($producto);
        $prodId = $producto->id_producto;

        // Reset inicial a unidades individuales (u.)
        $this->postJson("/api/productos/{$prodId}/unidades/0/seleccionar")->assertOk();
        $prodCheck = $this->getJson("/api/productos/{$prodId}");
        $this->assertEquals('u.', $prodCheck->json('data.unidad_medida'));
        $this->assertEquals(1.0, (float) $prodCheck->json('data.equivalencia_unidad'));

        // S08: Crear unidad de medida y establecerla como activa
        $maxEq = (float) (\App\Modules\Stock\Models\UnidadMedida::where('id_producto', $prodId)->max('equivalencia_base') ?? 10.0);
        $eq1 = $maxEq + 5.0;
        $eq2 = $maxEq + 15.0;

        $nombreUnidad = 'Display x' . rand(100, 999);
        $createRes = $this->postJson("/api/productos/{$prodId}/unidades", [
            'nombre_unidad' => $nombreUnidad,
            'equivalencia_base' => $eq1,
            'es_base' => true,
            'descripcion' => 'Caja o display contenedor',
        ]);
        $createRes->assertStatus(201);
        $idUnidad = $createRes->json('data.id_unidad');
        $this->assertNotNull($idUnidad);

        // Verificar que el producto ahora se visualiza con esa unidad de medida
        $prodRes = $this->getJson("/api/productos/{$prodId}");
        $prodRes->assertOk();
        $this->assertEquals($nombreUnidad, $prodRes->json('data.unidad_medida'));
        $this->assertEquals($eq1, (float) $prodRes->json('data.equivalencia_unidad'));

        // S08: Listar unidades del producto
        $listRes = $this->getJson("/api/productos/{$prodId}/unidades");
        $listRes->assertOk();
        $this->assertNotEmpty($listRes->json('data.unidades'));

        // S08: Modificar unidad
        $updateRes = $this->putJson("/api/productos/{$prodId}/unidades/{$idUnidad}", [
            'nombre_unidad' => $nombreUnidad . ' Editado',
            'equivalencia_base' => $eq2,
        ]);
        $updateRes->assertOk();

        // S08: Restablecer a unidades individuales (u.)
        $resetRes = $this->postJson("/api/productos/{$prodId}/unidades/0/seleccionar");
        $resetRes->assertOk();
        $this->assertEquals('u.', $resetRes->json('data.unidad_activa'));
        $this->assertEquals(1.0, (float) $resetRes->json('data.equivalencia_unidad'));

        // S08: Elegir nuevamente la unidad creada como activa para el producto
        $selectRes = $this->postJson("/api/productos/{$prodId}/unidades/{$idUnidad}/seleccionar");
        $selectRes->assertOk();
        $this->assertEquals($nombreUnidad . ' Editado', $selectRes->json('data.unidad_activa'));
        $this->assertEquals($eq2, (float) $selectRes->json('data.equivalencia_unidad'));

        // S08: Eliminar unidad creada
        $delRes = $this->deleteJson("/api/productos/{$prodId}/unidades/{$idUnidad}");
        $delRes->assertOk();
    }

    /**
     * S08 Rediseñada - Presentación comercial y formato de bulto directo en producto (opcional) y visualización en stock.
     */
    public function test_s08_presentacion_bulto_directa_en_producto_y_visualizacion_stock(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('prodFormatoBulto');
        $viewRes->assertSee('prodUnidadesPorBulto');
        $viewRes->assertSee('actualizarPreviewBultoProducto');

        $categoria = \App\Modules\Productos\Models\Categoria::first();
        $marca = \App\Modules\Productos\Models\Marca::first();
        $this->assertNotNull($categoria);
        $this->assertNotNull($marca);

        // 1. Alta con campos opcionales omitidos (debe tomar Unidad y 1 por defecto)
        $cod1 = 'TEST-BULTO-DEF-' . uniqid();
        $resDef = $this->postJson('/api/productos', [
            'codigo' => $cod1,
            'nombre' => 'Producto Suelto Test',
            'descripcion' => 'Producto sin bulto configurado',
            'precio_unitario' => 150.00,
            'id_categoria' => $categoria->id_categoria,
            'id_marca' => $marca->id_marca,
            'stock_disponible' => 50,
        ]);
        $resDef->assertStatus(201);
        $this->assertEquals('Unidad', $resDef->json('data.formato_bulto'));
        $this->assertEquals(1, $resDef->json('data.unidades_por_bulto'));

        // 2. Alta con presentación comercial explícita (Caja x 12)
        $cod2 = 'TEST-BULTO-CAJA-' . uniqid();
        $resCaja = $this->postJson('/api/productos', [
            'codigo' => $cod2,
            'nombre' => 'Alfajores Artesanales Caja',
            'descripcion' => 'Caja de alfajores x 12',
            'precio_unitario' => 1200.00,
            'id_categoria' => $categoria->id_categoria,
            'id_marca' => $marca->id_marca,
            'stock_disponible' => 120, // 120 unidades = 10 cajas
            'formato_bulto' => 'Caja',
            'unidades_por_bulto' => 12,
        ]);
        $resCaja->assertStatus(201);
        $idProdCaja = $resCaja->json('data.id_producto');
        $this->assertEquals('Caja', $resCaja->json('data.formato_bulto'));
        $this->assertEquals(12, $resCaja->json('data.unidades_por_bulto'));
        $this->assertEquals('Caja x 12 u.', $resCaja->json('data.presentacion_texto'));
        $this->assertEquals(120, $resCaja->json('data.stock'));

        // 3. Edición de presentación de producto
        $updateRes = $this->putJson("/api/productos/{$idProdCaja}", [
            'codigo' => $cod2,
            'nombre' => 'Alfajores Artesanales Display',
            'descripcion' => 'Display de alfajores x 24',
            'precio_unitario' => 1200.00,
            'id_categoria' => $categoria->id_categoria,
            'id_marca' => $marca->id_marca,
            'formato_bulto' => 'Display',
            'unidades_por_bulto' => 24,
        ]);
        $updateRes->assertOk();
        $this->assertEquals('Display', $updateRes->json('data.formato_bulto'));
        $this->assertEquals(24, $updateRes->json('data.unidades_por_bulto'));
        $this->assertEquals('Display x 24 u.', $updateRes->json('data.presentacion_texto'));

        // 4. Consulta individual del producto
        $getRes = $this->getJson("/api/productos/{$idProdCaja}");
        $getRes->assertOk();
        $this->assertEquals('Display', $getRes->json('data.formato_bulto'));
        $this->assertEquals(24, $getRes->json('data.unidades_por_bulto'));
        $this->assertEquals('Display x 24 u.', $getRes->json('data.presentacion_texto'));
    }

    /**
     * S08 - Unidad de Medida Activa Habilitada en Operaciones de Stock y Aclaración de Equivalencia.
     */
    public function test_s08_unidad_activa_habilitada_en_operaciones_stock_y_aclaracion_equivalencia(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        // Verificar elementos frontend para unidades en operaciones
        $viewRes->assertSee('ingresoUnidadSelect');
        $viewRes->assertSee('ingresoEquivalenciaBox');
        $viewRes->assertSee('onProductoIngresoChange', false);
        $viewRes->assertSee('ajusteUnidadSelect');
        $viewRes->assertSee('ajusteEquivalenciaBox');
        $viewRes->assertSee('onProductoAjusteChange', false);
        $viewRes->assertSee('devUnidadSelect');
        $viewRes->assertSee('devEquivalenciaBox');
        $viewRes->assertSee('modalDevUnidadSelect');
        $viewRes->assertSee('modalDevEquivalenciaBox');
        $viewRes->assertSee('onProductoDevolucionChange', false);

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($proveedor);
        $this->assertNotNull($producto);
        $prodId = $producto->id_producto;

        // Crear una unidad activa con equivalencia conocida
        $maxEq = (float) (\App\Modules\Stock\Models\UnidadMedida::where('id_producto', $prodId)->max('equivalencia_base') ?? 10.0);
        $eq = $maxEq + 10.0;
        $cantIngresoEsperada = 5 * (int) $eq;
        $cantAjusteEsperada = 2 * (int) $eq;
        $cantDevEsperada = 1 * (int) $eq;

        $nombreUnidad = 'Display x' . (int)$eq . ' ' . uniqid();
        $createRes = $this->postJson("/api/productos/{$prodId}/unidades", [
            'nombre_unidad' => $nombreUnidad,
            'equivalencia_base' => $eq,
            'es_base' => true,
            'descripcion' => "Display de {$eq} unidades para test de operaciones",
        ]);
        $createRes->assertStatus(201);
        $idUnidad = $createRes->json('data.id_unidad');
        $this->assertNotNull($idUnidad);

        // 1. Ingreso de Mercadería con unidad activa
        $stockAntesIngreso = (int) $producto->fresh()->stock_disponible;
        $ingresoRes = $this->postJson('/api/stock/ingresos', [
            'id_proveedor' => $proveedor->id_proveedor,
            'items' => [
                [
                    'id_producto' => $prodId,
                    'cantidad' => 5, // 5 Displays = 5 * $eq unidades base
                    'id_unidad' => $idUnidad,
                ]
            ],
            'observaciones' => 'Ingreso de 5 cajas con unidad activa',
        ]);
        $ingresoRes->assertStatus(201);
        $this->assertEquals($cantIngresoEsperada, $ingresoRes->json('data.items.0.cantidad_base'));
        $this->assertEquals($nombreUnidad, $ingresoRes->json('data.items.0.nombre_unidad'));
        $this->assertStringContainsString("{$cantIngresoEsperada} u. individuales", $ingresoRes->json('data.items.0.equivalencia_texto'));
        $this->assertStringContainsString("{$cantIngresoEsperada} u. individuales", $ingresoRes->json('message'));
        $this->assertEquals($stockAntesIngreso + $cantIngresoEsperada, (int) $producto->fresh()->stock_disponible);

        // 2. Ajuste Manual de Inventario con unidad activa
        $stockAntesAjuste = (int) $producto->fresh()->stock_disponible;
        $ajusteRes = $this->postJson('/api/stock/ajustes', [
            'id_producto' => $prodId,
            'tipo' => 'aumentar',
            'cantidad' => 2, // 2 Displays = 2 * $eq u. base
            'id_unidad' => $idUnidad,
            'motivo' => 'Ajuste manual de 2 displays por conteo',
        ]);
        $ajusteRes->assertStatus(201);
        $this->assertEquals($cantAjusteEsperada, $ajusteRes->json('data.cantidad_base'));
        $this->assertEquals($nombreUnidad, $ajusteRes->json('data.nombre_unidad'));
        $this->assertStringContainsString("{$cantAjusteEsperada} u. individuales", $ajusteRes->json('data.equivalencia_texto'));
        $this->assertStringContainsString('[Equivalencia:', $ajusteRes->json('message'));
        $this->assertEquals($stockAntesAjuste + $cantAjusteEsperada, (int) $producto->fresh()->stock_disponible);

        // 3. Devolución de Cliente con unidad activa
        $stockAntesDev = (int) $producto->fresh()->stock_disponible;
        $devClienteRes = $this->postJson('/api/stock/devoluciones', [
            'id_producto' => $prodId,
            'tipo_devolucion' => 'cliente',
            'cantidad' => 1, // 1 Display = 1 * $eq u. base
            'id_unidad' => $idUnidad,
            'motivo' => 'Devolución de cliente 1 display sellado',
        ]);
        $devClienteRes->assertStatus(201);
        $this->assertEquals($cantDevEsperada, $devClienteRes->json('data.cantidad_base'));
        $this->assertEquals($nombreUnidad, $devClienteRes->json('data.nombre_unidad'));
        $this->assertStringContainsString('[Equivalencia:', $devClienteRes->json('message'));
        $this->assertEquals($stockAntesDev + $cantDevEsperada, (int) $producto->fresh()->stock_disponible);

        // 4. Devolución a Proveedor con unidad activa
        $stockAntesDevProv = (int) $producto->fresh()->stock_disponible;
        $devProvRes = $this->postJson('/api/stock/devoluciones', [
            'id_producto' => $prodId,
            'tipo_devolucion' => 'proveedor',
            'cantidad' => 1, // 1 Display = 1 * $eq u. base
            'id_unidad' => $idUnidad,
            'motivo' => 'Devolución al proveedor por rotura de display',
        ]);
        $devProvRes->assertStatus(201);
        $this->assertEquals($cantDevEsperada, $devProvRes->json('data.cantidad_base'));
        $this->assertEquals($stockAntesDevProv - $cantDevEsperada, (int) $producto->fresh()->stock_disponible);

        // S08: Impedir eliminar la unidad porque ya está referenciada en movimientos de stock (integridad Kardex)
        $delFailRes = $this->deleteJson("/api/productos/{$prodId}/unidades/{$idUnidad}");
        $delFailRes->assertStatus(400);
        $this->assertStringContainsString('no se puede eliminar', strtolower($delFailRes->json('message')));

        // Restablecer el producto a unidades individuales por defecto (u.)
        $resetRes = $this->postJson("/api/productos/{$prodId}/unidades/0/seleccionar");
        $resetRes->assertOk();
        $this->assertEquals('u.', $resetRes->json('data.unidad_activa'));
        $this->assertEquals(1.0, (float) $resetRes->json('data.equivalencia_unidad'));
    }

    /**
     * S10, S11 - Compatibilidad y Redirección de Rutas de Vencimientos.
     */
    public function test_s10_s11_consulta_lotes_vencimientos_y_criticidad(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertDontSee('tabBtn-lotes');
        $viewRes->assertDontSee('pestana-lotes');

        // /vencimientos redirige ordenadamente a /inventario
        $this->get('/vencimientos')->assertRedirect('/inventario');

        // Endpoints S11 responden neutro/vacío sin error 500
        $proxRes = $this->getJson('/api/vencimientos/proximos');
        $proxRes->assertOk();

        $critRes = $this->getJson('/api/vencimientos/por-criticidad');
        $critRes->assertOk();

        $alertRes = $this->getJson('/api/vencimientos/alertas');
        $alertRes->assertOk();
    }

    /**
     * S12 - Historial de Movimientos de Stock (Kardex).
     */
    public function test_s12_historial_movimientos_stock_kardex(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('tabBtn-movimientos');
        $viewRes->assertSee('pestana-movimientos');
        $viewRes->assertSee('tablaMovimientosBody');
        $viewRes->assertSee('filtroTipoMovStock');
        $viewRes->assertSee('filtroProdMovStock');

        $movsRes = $this->getJson('/api/stock/movimientos');
        $movsRes->assertOk();
        $this->assertTrue($movsRes->json('status') === 'success');
    }

    /**
     * S13, S14 - Conteo Físico de Inventario y Ubicaciones Físicas.
     */
    public function test_s13_s14_conteo_fisico_y_ubicaciones_almacenamiento(): void
    {
        $viewRes = $this->get('/inventario');
        $viewRes->assertOk();
        $viewRes->assertSee('tabBtn-conteo');
        $viewRes->assertSee('pestana-conteo');
        $viewRes->assertSee('conteoStockSistema');
        $viewRes->assertSee('conteoPreviewBox');
        $viewRes->assertSee('tablaHistorialConteosBody');
        $viewRes->assertSee('modalUbicaciones');
        $viewRes->assertSee('ubicacionDescripcion');
        $viewRes->assertSee('btnCancelarEdicionUbicacion');
        $viewRes->assertSee('boxProductosUbicacion');
        $viewRes->assertSee('prodUbicacion');

        // S14: CRUD de Ubicaciones Físicas
        $nombreUbic = 'PASILLO-TEST-' . uniqid();
        $createUbicRes = $this->postJson('/api/ubicaciones', [
            'descripcion' => $nombreUbic,
        ]);
        $createUbicRes->assertStatus(201);
        $idUbic = $createUbicRes->json('data.id_ubicacion');
        $this->assertNotNull($idUbic);

        // S14: Modificar Ubicación
        $updateUbicRes = $this->putJson("/api/ubicaciones/{$idUbic}", [
            'descripcion' => $nombreUbic . ' MOD',
        ]);
        $updateUbicRes->assertOk();

        // S14: Listar Ubicaciones
        $ubicRes = $this->getJson('/api/ubicaciones');
        $ubicRes->assertOk();
        $this->assertTrue($ubicRes->json('status') === 'success');

        // S14: Eliminar ubicación no asignada
        $delUbicRes = $this->deleteJson("/api/ubicaciones/{$idUbic}");
        $delUbicRes->assertOk();

        // S14: Validar impedimento de borrar si está en uso
        $ubicEnUso = \App\Modules\Stock\Models\Ubicacion::has('productos')->first();
        if ($ubicEnUso) {
            $delUsedRes = $this->deleteJson("/api/ubicaciones/{$ubicEnUso->id_ubicacion}");
            $delUsedRes->assertStatus(400);
            $this->assertStringContainsString('no se puede eliminar', strtolower($delUsedRes->json('message')));
        }
    }

    // =========================================================================
    // MÓDULO PROVEEDORES (PV01 a PV07)
    // =========================================================================

    /**
     * PV01, PV02, PV03 - Alta, Consulta y Edición de Proveedores.
     */
    public function test_pv01_pv02_pv03_proveedores_crud_y_busqueda(): void
    {
        $viewRes = $this->get('/proveedores');
        $viewRes->assertOk();
        $viewRes->assertSee('modalProveedor');
        $viewRes->assertSee('provRazonSocial');
        $viewRes->assertSee('provCuit');
        $viewRes->assertSee('provContacto');
        $viewRes->assertSee('provTelefono');
        $viewRes->assertSee('provEmail');
        $viewRes->assertSee('provDireccion');
        $viewRes->assertSee('provPlazo');
        $viewRes->assertSee('filtroSearchProvs');
        $viewRes->assertSee('filtroEstadoProvs');

        // PV01: Alta de proveedor
        $cuitTest = '30-' . rand(10000000, 99999999) . '-' . rand(0, 9);
        $altaRes = $this->postJson('/api/proveedores', [
            'razon_social' => 'Distribuciones Test SA',
            'cuit' => $cuitTest,
            'contacto' => 'Carlos Gerente',
            'telefono' => '2923-456789',
            'correo' => 'contacto@testproveedor.com',
            'direccion' => 'Av. Independencia 450, Pigüé',
            'plazo_entrega_dias' => 7,
        ]);
        $altaRes->assertStatus(201);
        $provId = $altaRes->json('data.id_proveedor');
        $this->assertNotNull($provId);
        $this->assertEquals('Carlos Gerente', $altaRes->json('data.contacto'));
        $this->assertEquals('Av. Independencia 450, Pigüé', $altaRes->json('data.direccion'));
        $this->assertEquals('contacto@testproveedor.com', $altaRes->json('data.email'));
        $this->assertEquals($cuitTest, $altaRes->json('data.cuit'));

        // Validación CUIT duplicado
        $dupRes = $this->postJson('/api/proveedores', [
            'razon_social' => 'Otra Razón',
            'cuit' => $cuitTest,
        ]);
        $dupRes->assertStatus(400);

        // Validación: No permitir letras en CUIT
        $cuitConLetrasRes = $this->postJson('/api/proveedores', [
            'razon_social' => 'Proveedor Invalido CUIT',
            'cuit' => '30-ABC12345-9',
            'telefono' => '2923-456789',
        ]);
        $cuitConLetrasRes->assertStatus(400);
        $this->assertStringContainsString('no puede contener letras', strtolower($cuitConLetrasRes->json('message')));

        // Validación: No permitir letras en Teléfono
        $telConLetrasRes = $this->postJson('/api/proveedores', [
            'razon_social' => 'Proveedor Invalido Tel',
            'cuit' => '30-' . rand(10000000, 99999999) . '-1',
            'telefono' => '011-TEL-4567',
        ]);
        $telConLetrasRes->assertStatus(400);
        $this->assertStringContainsString('no puede contener letras', strtolower($telConLetrasRes->json('message')));

        // PV02: Buscar proveedor
        $searchRes = $this->getJson("/api/proveedores?search={$cuitTest}");
        $searchRes->assertOk();
        $this->assertNotEmpty($searchRes->json('data'));
        $this->assertEquals('Carlos Gerente', $searchRes->json('data.0.contacto'));
        $this->assertEquals('Av. Independencia 450, Pigüé', $searchRes->json('data.0.direccion'));
        $this->assertEquals('contacto@testproveedor.com', $searchRes->json('data.0.email'));

        // PV02: Consultar detalle
        $showRes = $this->getJson("/api/proveedores/{$provId}");
        $showRes->assertOk();
        $this->assertEquals('Distribuciones Test SA', $showRes->json('data.razon_social'));
        $this->assertEquals('Carlos Gerente', $showRes->json('data.contacto'));
        $this->assertEquals('Av. Independencia 450, Pigüé', $showRes->json('data.direccion'));
        $this->assertEquals('contacto@testproveedor.com', $showRes->json('data.email'));

        // PV03: Editar proveedor
        $editRes = $this->patchJson("/api/proveedores/{$provId}", [
            'razon_social' => 'Distribuciones Test Modificado SA',
            'cuit' => $cuitTest,
            'contacto' => 'Laura Administradora',
            'telefono' => '2923-999888',
            'email' => 'nuevo@testproveedor.com',
            'direccion' => 'Calle San Martín 100, Pigüé',
            'plazo_entrega_dias' => 10,
        ]);
        $editRes->assertOk();
        $this->assertEquals('Distribuciones Test Modificado SA', $editRes->json('data.razon_social'));
        $this->assertEquals('Laura Administradora', $editRes->json('data.contacto'));
        $this->assertEquals('nuevo@testproveedor.com', $editRes->json('data.email'));
        $this->assertEquals('Calle San Martín 100, Pigüé', $editRes->json('data.direccion'));
        $this->assertEquals(10, $editRes->json('data.plazo_entrega_dias'));
    }

    /**
     * PV04 - Desactivar y Activar Proveedor (Borrado Lógico).
     */
    public function test_pv04_desactivar_activar_proveedor_borrado_logico(): void
    {
        $viewRes = $this->get('/proveedores');
        $viewRes->assertOk();
        $viewRes->assertSee('cambiarEstadoProveedor');

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $this->assertNotNull($proveedor);
        $id = $proveedor->id_proveedor;

        // Desactivar
        $desactRes = $this->patchJson("/api/proveedores/{$id}/desactivar");
        $desactRes->assertOk();
        $this->assertEquals('inactivo', $desactRes->json('data.estado'));

        // Intentar operaciones con proveedor dado de baja (deben ser rechazadas con 400)
        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($producto);

        // 1. Ingreso de mercadería
        $ingresoFail = $this->postJson('/api/stock/ingresos', [
            'id_proveedor' => $id,
            'fecha' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 10,
                    'nro_lote' => 'LOTE-TEST-INACTIVO',
                    'fecha_vencimiento' => now()->addMonths(6)->toDateString(),
                ]
            ]
        ]);
        $ingresoFail->assertStatus(400);

        // 2. Crear compra
        $compraFail = $this->postJson('/api/compras', [
            'id_proveedor' => $id,
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 5,
                    'precio_unitario' => 100,
                ]
            ]
        ]);
        $compraFail->assertStatus(400);

        // 3. Crear orden de compra
        $ordenFail = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $id,
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_solicitada' => 5,
                    'precio_estimado' => 100,
                ]
            ]
        ]);
        $ordenFail->assertStatus(400);

        // 4. Asociar productos
        $asocFail = $this->postJson("/api/proveedores/{$id}/productos", [
            'id_productos' => [$producto->id_producto],
            'precio_acordado' => 100,
        ]);
        $asocFail->assertStatus(400);

        // 5. Modificar plazo de entrega
        $plazoFail = $this->postJson("/api/proveedores/{$id}/plazo", [
            'plazo_entrega_dias' => 12,
        ]);
        $plazoFail->assertStatus(400);

        // Reactivar
        $actRes = $this->patchJson("/api/proveedores/{$id}/activar");
        $actRes->assertOk();
        $this->assertEquals('activo', $actRes->json('data.estado'));
    }

    /**
     * PV05 - Asociar Productos a Proveedores.
     */
    public function test_pv05_asociar_productos_a_proveedores(): void
    {
        $viewRes = $this->get('/proveedores');
        $viewRes->assertOk();
        $viewRes->assertSee('modalAsociarProductos');
        $viewRes->assertSee('modalEditarAsociacion');
        $viewRes->assertSee('asocProductoSelect');
        $viewRes->assertSee('asocPrecioAcordado');
        $viewRes->assertSee('asocEsPrincipal');
        $viewRes->assertSee('tablaProductosAsociadosBody');
        $viewRes->assertSee('asocCantidadTotal');
        $viewRes->assertSee('abrirModalEditarAsociacion');

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($proveedor);
        $this->assertNotNull($producto);

        // 1. Asociar enviando id_producto singular (lo que enviaba el formulario)
        $asocResSingular = $this->postJson("/api/proveedores/{$proveedor->id_proveedor}/productos", [
            'id_producto' => $producto->id_producto,
            'precio_acordado' => 1250.00,
            'es_proveedor_principal' => true,
        ]);
        $asocResSingular->assertStatus(201);
        $this->assertTrue($asocResSingular->json('status') === 'success');

        // 2. Asociar enviando id_productos array
        $asocResArray = $this->postJson("/api/proveedores/{$proveedor->id_proveedor}/productos", [
            'id_productos' => [$producto->id_producto],
            'precio_acordado' => 1300.00,
            'es_proveedor_principal' => true,
        ]);
        $asocResArray->assertStatus(201);
        $this->assertTrue($asocResArray->json('status') === 'success');

        // 3. Consultar listado de productos asociados al proveedor
        $listRes = $this->getJson("/api/proveedores/{$proveedor->id_proveedor}/productos");
        $listRes->assertOk();
        $listRes->assertJsonStructure([
            'status',
            'proveedor' => ['id_proveedor', 'razon_social'],
            'data' => [
                '*' => [
                    'id_producto_proveedor',
                    'id_producto',
                    'codigo',
                    'precio_acordado',
                    'es_proveedor_principal',
                ]
            ]
        ]);
        $encontrado = collect($listRes->json('data'))->firstWhere('id_producto', $producto->id_producto);
        $this->assertNotNull($encontrado);
        $this->assertEquals(1300.00, (float) $encontrado['precio_acordado']);
        $this->assertTrue((bool) $encontrado['es_proveedor_principal']);

        // 4. Consultar detalle individual de la asociación
        $idAsoc = $encontrado['id_producto_proveedor'];
        $getRes = $this->getJson("/api/proveedores/productos/{$idAsoc}");
        $getRes->assertOk();
        $this->assertEquals(1300.00, (float) $getRes->json('data.precio_acordado'));

        // 5. Editar / Modificar la asociación
        $editRes = $this->putJson("/api/proveedores/productos/{$idAsoc}", [
            'precio_acordado' => 1450.50,
            'es_proveedor_principal' => false,
        ]);
        $editRes->assertOk();
        $this->assertEquals('success', $editRes->json('status'));
        $this->assertEquals(1450.50, (float) $editRes->json('data.precio_acordado'));
        $this->assertFalse((bool) $editRes->json('data.es_proveedor_principal'));

        // Verificar en base de datos
        $asocDB = ProductoProveedor::find($idAsoc);
        $this->assertNotNull($asocDB);
        $this->assertEquals(1450.50, (float) $asocDB->precio_acordado);
        $this->assertFalse((bool) $asocDB->es_proveedor_principal);
    }

    /**
     * PV05 / Sync - Sincronizar asociaciones históricas de productos con proveedores.
     */
    public function test_pv05_sincronizar_asociaciones_historicas(): void
    {
        $viewRes = $this->get('/proveedores');
        $viewRes->assertOk();
        $viewRes->assertSee('sincronizarAsociacionesAutomaticas');

        $syncRes = $this->postJson('/api/proveedores/sincronizar-asociaciones');
        $syncRes->assertOk();
        $syncRes->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'nuevas',
                'actualizadas',
                'total_asociaciones',
            ]
        ]);
        $this->assertEquals('success', $syncRes->json('status'));
    }

    /**
     * PV06, PV07 - Precios Históricos y Plazos de Entrega del Proveedor.
     */
    public function test_pv06_pv07_plazos_entrega_proveedor(): void
    {
        $viewRes = $this->get('/proveedores');
        $viewRes->assertOk();
        $viewRes->assertSee('kpiPlazoPromedio');
        $viewRes->assertSee('modalPreciosHistoricos');
        $viewRes->assertSee('tablaPreciosHistoricosBody');
        $viewRes->assertSee('filtroHistProveedor');
        $viewRes->assertSee('filtroHistProducto');

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $this->assertNotNull($proveedor);

        // PV07: Registrar plazo
        $plazoRes = $this->postJson("/api/proveedores/{$proveedor->id_proveedor}/plazo", [
            'plazo_entrega_dias' => 12,
            'motivo' => 'Ajuste estacional de logística',
        ]);
        $plazoRes->assertStatus(201);

        // PV07: Consultar historial de plazos
        $histRes = $this->getJson("/api/proveedores/{$proveedor->id_proveedor}/plazos");
        $histRes->assertOk();

        // PV06: Consultar precios históricos del proveedor
        $preciosRes = $this->getJson("/api/proveedores/{$proveedor->id_proveedor}/precios-historicos");
        $preciosRes->assertOk()
            ->assertJsonStructure(['status', 'proveedor', 'data', 'resumen']);

        // PV06: Comparativa de aumentos
        $compRes = $this->getJson("/api/proveedores/{$proveedor->id_proveedor}/comparativa-aumentos");
        $compRes->assertOk()
            ->assertJsonStructure(['status', 'data', 'resumen']);
    }

    /**
     * Registro de auditoría e historial de Proveedores (Alta, Modificaciones, Baja).
     */
    public function test_proveedores_historial_alta_modificacion_baja(): void
    {
        $viewRes = $this->get('/proveedores');
        $viewRes->assertOk();
        $viewRes->assertSee('modalHistorialProveedor');
        $viewRes->assertSee('verHistorialProveedor');

        // 1. Alta de un nuevo proveedor
        $randomCuit = '30-' . rand(10000000, 99999999) . '-1';
        $altaRes = $this->postJson('/api/proveedores', [
            'razon_social' => 'Proveedor Historial Test S.A.',
            'cuit' => $randomCuit,
            'contacto' => 'Juan Auditor',
            'telefono' => '11-4455-6677',
            'email' => 'auditor@historial.com',
            'direccion' => 'Calle Auditoría 123',
            'plazo_entrega_dias' => 7,
        ]);
        $altaRes->assertStatus(201);
        $idProveedor = $altaRes->json('data.id_proveedor');
        $this->assertNotNull($idProveedor);

        // 2. Modificación de datos
        $modRes = $this->patchJson("/api/proveedores/{$idProveedor}", [
            'razon_social' => 'Proveedor Historial Test Modificado S.A.',
            'cuit' => $randomCuit,
            'telefono' => '11-9988-7766',
            'plazo_entrega_dias' => 14,
        ]);
        $modRes->assertOk();

        // 3. Baja / desactivación
        $bajaRes = $this->patchJson("/api/proveedores/{$idProveedor}/desactivar");
        $bajaRes->assertOk();

        // 4. Reactivación
        $reactRes = $this->patchJson("/api/proveedores/{$idProveedor}/activar");
        $reactRes->assertOk();

        // 5. Consultar historial del proveedor
        $histRes = $this->getJson("/api/proveedores/{$idProveedor}/historial");
        $histRes->assertOk();
        $histRes->assertJsonStructure([
            'status',
            'proveedor' => ['id_proveedor', 'razon_social', 'cuit', 'estado'],
            'data' => [
                '*' => [
                    'id_proveedor',
                    'tipo_evento',
                    'titulo',
                    'usuario_nombre',
                    'fecha_hora',
                ]
            ]
        ]);

        $eventos = collect($histRes->json('data'));
        $tipos = $eventos->pluck('tipo_evento')->all();
        $this->assertContains('alta', $tipos);
        $this->assertContains('modificacion', $tipos);
        $this->assertContains('baja', $tipos);
        $this->assertContains('reactivacion', $tipos);

        // 6. Consultar historial global
        $globalRes = $this->getJson('/api/proveedores/historial');
        $globalRes->assertOk();
        $this->assertTrue(count($globalRes->json('data')) > 0);
    }

    // =========================================================================
    // MÓDULO COMPRAS (C01 a C09)
    // =========================================================================

    /**
     * C01, C02, C03 - Registrar, Consultar y Detalle de Compras.
     */
    public function test_c01_c02_c03_compras_registro_consulta_y_detalle(): void
    {
        $viewRes = $this->get('/compras');
        $viewRes->assertOk();
        $viewRes->assertSee('tabBtn-compras');
        $viewRes->assertSee('modalNuevaCompra');
        $viewRes->assertSee('compraProveedor');
        $viewRes->assertSee('compraComprobante');
        $viewRes->assertSee('detalleCompraItems');
        $viewRes->assertSee('modalDetalleCompra');

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($proveedor);
        $this->assertNotNull($producto);

        // C01: Registrar Compra
        $compraRes = $this->postJson('/api/compras', [
            'id_proveedor' => $proveedor->id_proveedor,
            'numero_comprobante' => 'FC-TEST-' . rand(1000, 9999),
            'fecha_compra' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 10,
                    'precio_unitario' => 1200.00,
                ]
            ]
        ]);
        $compraRes->assertStatus(201);
        $compraId = $compraRes->json('data.id_compra');
        $this->assertNotNull($compraId);
        $this->assertEquals(12000.00, (float)$compraRes->json('data.importe_total'));

        // C02: Consultar compras
        $listRes = $this->getJson('/api/compras');
        $listRes->assertOk();

        // C03: Consultar detalle de compra
        $showRes = $this->getJson("/api/compras/{$compraId}");
        $showRes->assertOk();
        $this->assertEquals($compraId, $showRes->json('data.compra.id_compra'));
    }

    /**
     * C04, C05 - Modificar y Cancelar Compra Pendiente.
     */
    public function test_c04_c05_compras_modificar_y_cancelar_pendiente(): void
    {
        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();

        $compraRes = $this->postJson('/api/compras', [
            'id_proveedor' => $proveedor->id_proveedor,
            'numero_comprobante' => 'FC-CANC-' . rand(1000, 9999),
            'fecha_compra' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 5,
                    'precio_unitario' => 1000.00,
                ]
            ]
        ]);
        $compraRes->assertStatus(201);
        $compraId = $compraRes->json('data.id_compra');

        // C05: Cancelar compra
        $cancRes = $this->patchJson("/api/compras/{$compraId}/cancelar", [
            'motivo' => 'Cancelación por cambio de pedido',
        ]);
        $cancRes->assertOk();
        $this->assertEquals('cancelada', $cancRes->json('data.estado'));
    }

    /**
     * C06, C07 - Recepción Parcial de Mercadería y Estados de Compra.
     */
    public function test_c06_c07_compras_recepcion_parcial_lotes_y_estados(): void
    {
        $viewRes = $this->get('/compras');
        $viewRes->assertOk();
        $viewRes->assertSee('modalRecepcion');

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();

        $compraRes = $this->postJson('/api/compras', [
            'id_proveedor' => $proveedor->id_proveedor,
            'numero_comprobante' => 'FC-RECEP-' . rand(1000, 9999),
            'fecha_compra' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 10,
                    'precio_unitario' => 1000.00,
                ]
            ]
        ]);
        $compraRes->assertStatus(201);
        $compraId = $compraRes->json('data.id_compra');

        // C07: Visualizar estado de compra
        $estadoRes = $this->getJson("/api/compras/{$compraId}/estado");
        $estadoRes->assertOk();
        $this->assertEquals('pendiente', $estadoRes->json('data.estado'));

        // C06: Recepción Parcial (5 de 10)
        $stockAntesRecep = (int) $producto->fresh()->stock_actual;
        $recepRes = $this->postJson("/api/compras/{$compraId}/recepciones", [
            'fecha_recepcion' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_recibida' => 5,
                ]
            ]
        ]);
        $recepRes->assertStatus(201);

        // Estado debe avanzar a parcialmente_recibida
        $estadoPostRes = $this->getJson("/api/compras/{$compraId}/estado");
        $estadoPostRes->assertOk();
        $this->assertEquals('parcialmente_recibida', $estadoPostRes->json('data.estado'));

        // Verificar incremento directo en stock y detalle_recepcion con id_lote null
        $this->assertEquals($stockAntesRecep + 5, (int) $producto->fresh()->stock_actual);
        $this->assertDatabaseHas('DETALLE_RECEPCION', [
            'id_producto' => $producto->id_producto,
            'cantidad_recibida' => 5,
            'id_lote' => null,
        ]);
    }

    /**
     * C08, C09 - Registrar Múltiples Métodos de Pago y Saldo Adeudado.
     */
    public function test_c08_c09_compras_multiples_metodos_pago_y_saldos(): void
    {
        $viewRes = $this->get('/compras');
        $viewRes->assertOk();
        $viewRes->assertSee('modalPago');
        $viewRes->assertSee('tabBtn-deudas');
        $viewRes->assertSee('pestana-deudas');

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();

        $compraRes = $this->postJson('/api/compras', [
            'id_proveedor' => $proveedor->id_proveedor,
            'numero_comprobante' => 'FC-PAGO-' . rand(1000, 9999),
            'fecha_compra' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 10,
                    'precio_unitario' => 1000.00,
                ]
            ]
        ]);
        $compraRes->assertStatus(201);
        $compraId = $compraRes->json('data.id_compra');

        // Registrar pago parcial de $4000
        $pagoRes = $this->postJson("/api/compras/{$compraId}/pagos", [
            'metodo_pago' => 'transferencia',
            'importe' => 4000.00,
            'fecha_pago' => now()->toDateString(),
        ]);
        $pagoRes->assertStatus(201);

        // C09: Resumen de saldo adeudado por compra
        $resumenRes = $this->getJson("/api/compras/{$compraId}/pagos/resumen");
        $resumenRes->assertOk();
        $this->assertEquals(10000.00, (float)$resumenRes->json('data.importe_total'));
        $this->assertEquals(4000.00, (float)$resumenRes->json('data.total_pagado'));
        $this->assertEquals(6000.00, (float)$resumenRes->json('data.saldo_a_pagar'));

        // C09: Endpoint global de deudas con proveedores (Issue #42)
        $deudasRes = $this->getJson('/api/compras/deudas');
        $deudasRes->assertOk();
        $this->assertTrue(collect($deudasRes->json('data.por_proveedor') ?? $deudasRes->json('data.proveedores'))->where('id_proveedor', $proveedor->id_proveedor)->isNotEmpty());
    }

    // =========================================================================
    // MÓDULO PEDIDOS DE COMPRA (PC01 a PC07)
    // =========================================================================

    /**
     * PC01, PC02, PC03 - Generar, Listar y Detalle de Órdenes de Compra.
     */
    public function test_pc01_pc02_pc03_ordenes_compra_emision_consulta_y_detalle(): void
    {
        $viewRes = $this->get('/compras');
        $viewRes->assertOk();
        $viewRes->assertSee('tabBtn-ordenes');
        $viewRes->assertSee('modalNuevaOrden');
        $viewRes->assertSee('ordenProveedor');
        $viewRes->assertSee('modalDetalleOrden');

        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();
        $this->assertNotNull($proveedor);
        $this->assertNotNull($producto);

        // PC01: Generar orden de compra
        $ordenRes = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $proveedor->id_proveedor,
            'fecha_creacion' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_solicitada' => 25,
                    'precio_estimado' => 950.00,
                ]
            ]
        ]);
        $ordenRes->assertStatus(201);
        $ordenId = $ordenRes->json('data.id_orden');
        $this->assertNotNull($ordenId);
        $this->assertEquals(23750.00, (float)$ordenRes->json('data.total_estimado'));

        // PC02: Listar órdenes
        $listRes = $this->getJson('/api/ordenes-compra');
        $listRes->assertOk();

        // PC03: Detalle de orden
        $showRes = $this->getJson("/api/ordenes-compra/{$ordenId}");
        $showRes->assertOk();
        $this->assertEquals($ordenId, $showRes->json('data.orden.id_orden'));
    }

    /**
     * PC04, PC05, PC06 - Edición, Cancelación y Cambio de Estado de Órdenes de Compra.
     */
    public function test_pc04_pc05_pc06_ordenes_compra_edicion_cancelacion_y_estados(): void
    {
        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();

        // Orden 1: Probar envío (PC06)
        $orden1Res = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $proveedor->id_proveedor,
            'fecha_creacion' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_solicitada' => 10,
                    'precio_estimado' => 500.00,
                ]
            ]
        ]);
        $orden1Res->assertStatus(201);
        $orden1Id = $orden1Res->json('data.id_orden');

        // PC04: Modificar orden de compra en estado pendiente
        $editRes = $this->putJson("/api/ordenes-compra/{$orden1Id}", [
            'id_proveedor' => $proveedor->id_proveedor,
            'fecha_creacion' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_solicitada' => 15,
                    'precio_estimado' => 500.00,
                ]
            ]
        ]);
        $editRes->assertOk();
        $this->assertEquals(7500.00, (float) $editRes->json('data.total_estimado'));
        $this->assertEquals(now()->toDateString(), $editRes->json('data.fecha_modificacion'));

        // PC06: Enviar orden
        $enviarRes = $this->patchJson("/api/ordenes-compra/{$orden1Id}/enviar");
        $enviarRes->assertOk();
        $this->assertEquals('enviada', $enviarRes->json('data.estado'));

        // PC04: Rechazar edición si la orden ya fue enviada
        $editSentRes = $this->putJson("/api/ordenes-compra/{$orden1Id}", [
            'id_proveedor' => $proveedor->id_proveedor,
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_solicitada' => 20,
                    'precio_estimado' => 500.00,
                ]
            ]
        ]);
        $editSentRes->assertStatus(400);

        // Orden 2: Probar cancelación de orden pendiente (PC05)
        $orden2Res = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $proveedor->id_proveedor,
            'fecha_creacion' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_solicitada' => 5,
                    'precio_estimado' => 600.00,
                ]
            ]
        ]);
        $orden2Res->assertStatus(201);
        $orden2Id = $orden2Res->json('data.id_orden');

        // PC05: Cancelar orden pendiente
        $cancRes = $this->patchJson("/api/ordenes-compra/{$orden2Id}/cancelar");
        $cancRes->assertOk();
        $this->assertEquals('cancelada', $cancRes->json('data.estado'));
        $this->assertEquals(now()->toDateString(), $cancRes->json('data.fecha_cancelacion'));

        // PC05: Conservar historial y detalle de la orden cancelada
        $showCancRes = $this->getJson("/api/ordenes-compra/{$orden2Id}");
        $showCancRes->assertOk();
        $this->assertEquals('cancelada', $showCancRes->json('data.orden.estado'));
        $this->assertCount(1, $showCancRes->json('data.detalles'));

        // PC05: Impedir cancelar una orden no pendiente (ej. enviada o completada)
        $cancSentRes = $this->patchJson("/api/ordenes-compra/{$orden1Id}/cancelar");
        $cancSentRes->assertStatus(400);
        $this->assertStringContainsString('Solo se pueden cancelar órdenes pendientes', $cancSentRes->json('message'));

        // PC06: Visualizar avance de estado: enviada -> recibida_parcialmente -> completada
        $parcialRes = $this->patchJson("/api/ordenes-compra/{$orden1Id}/cambiar-estado", [
            'estado' => 'recibida_parcialmente',
        ]);
        $parcialRes->assertOk();
        $this->assertEquals('recibida_parcialmente', $parcialRes->json('data.estado'));

        $compRes = $this->patchJson("/api/ordenes-compra/{$orden1Id}/cambiar-estado", [
            'estado' => 'completada',
        ]);
        $compRes->assertOk();
        $this->assertEquals('completada', $compRes->json('data.estado'));

        // PC06: Consultar y filtrar órdenes por estado
        $filtroPend = $this->getJson('/api/ordenes-compra?estado=pendiente');
        $filtroPend->assertOk();
        $filtroComp = $this->getJson('/api/ordenes-compra?estado=completada');
        $filtroComp->assertOk();
        $this->assertTrue(collect($filtroComp->json('data'))->every(fn ($o) => $o['estado'] === 'completada'));

        // PC06: Visualización en frontend (/compras) de filtros y selector de estados
        $viewCompras = $this->get('/compras');
        $viewCompras->assertOk();
        $viewCompras->assertSee('filtroEstadoOrdenes');
        $viewCompras->assertSee('recibida_parcialmente');
        $viewCompras->assertSee('completada');
    }

    /**
     * PC07 - Sugerencias de Reposición y Dashboard de Métricas.
     */
    public function test_pc07_sugerencias_reposicion_y_dashboard(): void
    {
        $viewRes = $this->get('/compras');
        $viewRes->assertOk();
        $viewRes->assertSee('tabBtn-sugerencias');
        $viewRes->assertSee('pestana-sugerencias');

        $viewSuger = $this->get('/sugerencias-reposicion');
        $viewSuger->assertOk();
        $viewSuger->assertSee('tablaSugerencias');
        $viewSuger->assertSee('bodySugerencias');

        $viewDash = $this->get('/dashboard-reposicion');
        $viewDash->assertOk();
        $viewDash->assertSee('Dashboard de Reposición');

        // Endpoints PC07
        $genSugRes = $this->postJson('/api/sugerencias-reposicion/generar');
        $genSugRes->assertStatus(201);
        $genSugRes->assertJsonPath('exito', true);

        $listSugRes = $this->getJson('/api/sugerencias-reposicion');
        $listSugRes->assertOk();
        $listSugRes->assertJsonPath('exito', true);

        $sugerencias = $listSugRes->json('datos');
        if (!empty($sugerencias)) {
            $primeraId = $sugerencias[0]['id'];
            $genOrdenRes = $this->postJson("/api/sugerencias-reposicion/{$primeraId}/generar-orden");
            $genOrdenRes->assertStatus(201);
            $genOrdenRes->assertJsonPath('exito', true);
            $this->assertNotNull($genOrdenRes->json('orden.id'));
            $this->assertNotNull($genOrdenRes->json('orden.id_proveedor'));

            // No permitir duplicar la orden sobre la misma sugerencia ya procesada
            $reintentarRes = $this->postJson("/api/sugerencias-reposicion/{$primeraId}/generar-orden");
            $reintentarRes->assertStatus(400);
        }

        $resumenSugRes = $this->getJson('/api/sugerencias-reposicion/resumen');
        $resumenSugRes->assertOk();

        $dashRes = $this->getJson('/api/dashboard-reposicion/resumen');
        $dashRes->assertOk();
    }

    /**
     * Ciclo Integral de Distribuidora:
     * PRODUCTOS -> STOCK -> PEDIDOS DE COMPRA -> PROVEEDORES -> COMPRAS -> STOCK (Lotes/Vencimientos) -> PAGOS/DEUDAS
     */
    public function test_ciclo_completo_distribuidora_reposicion_compras_y_stock(): void
    {
        $proveedor = Proveedor::where('estado', 'activo')->first();
        $producto = Producto::where('estado', 'activo')->first();

        $this->assertNotNull($proveedor);
        $this->assertNotNull($producto);

        // 1. Stock inicial del producto
        $stockInicial = (int) $producto->stock_actual;

        // 2. PEDIDO DE COMPRA (PC01): Crear orden de compra
        $ordenRes = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $proveedor->id_proveedor,
            'fecha_creacion' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_solicitada' => 15,
                    'precio_estimado' => 1200.00,
                ]
            ]
        ]);
        $ordenRes->assertStatus(201);
        $ordenId = $ordenRes->json('data.id_orden');

        // 3. PEDIDO DE COMPRA (PC06): Enviar orden al proveedor
        $enviarRes = $this->patchJson("/api/ordenes-compra/{$ordenId}/enviar");
        $enviarRes->assertOk();
        $this->assertEquals('enviada', $enviarRes->json('data.estado'));

        // 4. COMPRAS (C01): Convertir orden a compra confirmada/facturada
        $nroComprobante = 'FAC-CICLO-' . rand(1000, 9999);
        $compraRes = $this->postJson('/api/compras', [
            'id_proveedor' => $proveedor->id_proveedor,
            'id_orden' => $ordenId,
            'numero_comprobante' => $nroComprobante,
            'fecha_compra' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(20)->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => 15,
                    'precio_unitario' => 1200.00,
                ]
            ]
        ]);
        $compraRes->assertStatus(201);
        $compraId = $compraRes->json('data.id_compra');
        $this->assertEquals(18000.00, (float)$compraRes->json('data.importe_total'));

        // 5. RECEPCIÓN & STOCK (C06, S03): Recepción total directa a stock del producto
        $recepRes = $this->postJson("/api/compras/{$compraId}/recepciones", [
            'fecha_recepcion' => now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $producto->id_producto,
                    'cantidad_recibida' => 15,
                ]
            ]
        ]);
        $recepRes->assertStatus(201);
        $this->assertEquals('completada', $recepRes->json('data.estado_compra'));

        // 6. Verificar actualización del Stock directo del producto
        $producto->refresh();
        $this->assertEquals($stockInicial + 15, (int)$producto->stock_actual);

        // 7. Verificar que la orden vinculada avanzó automáticamente a completada (PC06)
        $ordenDb = OrdenCompra::find($ordenId);
        $this->assertEquals('completada', $ordenDb->estado);

        // 8. PAGOS Y DEUDAS (C08, C09): Pagar parte de la compra y auditar saldo
        $pagoRes = $this->postJson("/api/compras/{$compraId}/pagos", [
            'metodo_pago' => 'echeq',
            'importe' => 8000.00,
            'fecha_pago' => now()->toDateString(),
        ]);
        $pagoRes->assertStatus(201);

        $resumenPago = $this->getJson("/api/compras/{$compraId}/pagos/resumen");
        $resumenPago->assertOk();
        $this->assertEquals(18000.00, (float)$resumenPago->json('data.importe_total'));
        $this->assertEquals(8000.00, (float)$resumenPago->json('data.total_pagado'));
        $this->assertEquals(10000.00, (float)$resumenPago->json('data.saldo_a_pagar'));

        // 9. Auditoría de Deudas del Proveedor (C09)
        $deudasRes = $this->getJson('/api/compras/deudas');
        $deudasRes->assertOk();
        $provDeuda = collect($deudasRes->json('data.por_proveedor') ?? $deudasRes->json('data.proveedores'))->firstWhere('id_proveedor', $proveedor->id_proveedor);
        $this->assertNotNull($provDeuda);
        $this->assertGreaterThanOrEqual(10000.00, (float)($provDeuda['total_adeudado'] ?? $provDeuda['saldo_adeudado']));
    }
}
