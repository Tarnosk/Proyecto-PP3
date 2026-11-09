<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\HistorialPrecio;
use App\Modules\Productos\Models\Marca;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\UsesG3Database;

class ProductoCostoRecargoTest extends TestCase
{
    use DatabaseTransactions, UsesG3Database {
        UsesG3Database::beginDatabaseTransaction insteadof DatabaseTransactions;
    }

    protected User $admin;

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

        $this->signInAs($this->admin);
    }

    private function crearProducto(array $atributos = []): Producto
    {
        $cat = Categoria::first() ?? Categoria::create(['nombre' => 'Cat Test ' . uniqid(), 'activo' => true]);
        $marca = Marca::first() ?? Marca::create(['nombre' => 'Marca Test ' . uniqid(), 'activo' => true]);

        return Producto::create(array_merge([
            'codigo' => 'TEST-COSTO-' . uniqid(),
            'nombre' => 'Producto Prueba Costo ' . uniqid(),
            'descripcion' => 'Descripción de prueba',
            'precio_unitario' => 1400.00,
            'costo' => 1000.00,
            'recargo' => 40.00,
            'stock' => 50,
            'stock_minimo' => 5,
            'estado' => 'activo',
            'id_categoria' => $cat->id_categoria,
            'id_marca' => $marca->id_marca,
            'id_usuario_carga' => $this->admin->id,
        ], $atributos));
    }

    private function crearProveedor(string $cuit = null): Proveedor
    {
        return Proveedor::create([
            'razon_social' => 'Proveedor Reposición ' . uniqid(),
            'contacto' => 'Contacto Test',
            'cuit' => $cuit ?? '30-' . rand(10000000, 99999999) . '-1',
            'telefono' => '1155667788',
            'email' => 'prov' . uniqid() . '@test.com',
            'direccion' => 'Calle Falsa 123',
            'estado' => 'activo',
            'fecha_alta' => Carbon::now()->toDateString(),
            'plazo_entrega_dias' => 5,
            'id_usuario_carga' => $this->admin->id,
        ]);
    }

    /**
     * Verifica que los accessors de costo, recargo y margen comercial calculen correctamente.
     */
    public function test_calculo_margen_ganancia_y_recargo(): void
    {
        // Costo = 1000, Recargo = 40% -> Precio = 1400
        // Ganancia = 400. Margen = 400 / 1400 = 28.57%
        $prod = $this->crearProducto([
            'precio_unitario' => 1400.00,
            'costo' => 1000.00,
            'recargo' => 40.00,
        ]);

        $this->assertSame(1000.0, (float) $prod->costo);
        $this->assertSame(40.0, (float) $prod->recargo);
        $this->assertSame(28.57, (float) $prod->margen_ganancia);
    }

    /**
     * Sincronización de costo de reposición desde el proveedor principal.
     */
    public function test_sincronizar_costo_reposicion_desde_proveedor_principal(): void
    {
        $prod = $this->crearProducto([
            'precio_unitario' => 1400.00,
            'costo' => 1000.00,
            'recargo' => 40.00,
        ]);

        $prov = $this->crearProveedor();

        ProductoProveedor::create([
            'id_producto' => $prod->id_producto,
            'id_proveedor' => $prov->id_proveedor,
            'es_proveedor_principal' => true,
            'precio_acordado' => 1200.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->toDateString(),
            'id_usuario' => $this->admin->id,
        ]);

        // Sincronizar costo: costo pasa a 1200, precio pasa a 1200 * 1.40 = 1680.00
        $res = $prod->sincronizarCostoReposicion($this->admin->id);
        $this->assertTrue($res);

        $prod->refresh();
        $this->assertSame(1200.0, (float) $prod->costo);
        $this->assertSame(1680.0, (float) $prod->precio_unitario);

        // Se debió registrar en HistorialPrecio con origen costo_reposicion
        $ultimoHistorial = HistorialPrecio::where('id_producto', $prod->id_producto)
            ->orderByDesc('id_historial')
            ->first();

        $this->assertNotNull($ultimoHistorial);
        $this->assertSame('costo_reposicion', $ultimoHistorial->origen);
        $this->assertSame(1680.0, (float) $ultimoHistorial->precio);
    }

    /**
     * Al registrar un nuevo precio acordado para el proveedor principal (PV06),
     * el costo del producto se sincroniza y el precio de venta se recalcula automáticamente.
     */
    public function test_actualizar_precio_proveedor_principal_sincroniza_costo_y_recalcula_precio_venta(): void
    {
        $prod = $this->crearProducto([
            'precio_unitario' => 1300.00,
            'costo' => 1000.00,
            'recargo' => 30.00,
        ]);

        $prov = $this->crearProveedor();

        $pp = ProductoProveedor::create([
            'id_producto' => $prod->id_producto,
            'id_proveedor' => $prov->id_proveedor,
            'es_proveedor_principal' => true,
            'precio_acordado' => 1000.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->toDateString(),
            'id_usuario' => $this->admin->id,
        ]);

        // Proveedor aumenta a 1500
        $response = $this->postJson("/api/proveedores/{$prov->id_proveedor}/productos/{$prod->id_producto}/precio", [
            'precio_acordado' => 1500.00,
            'observaciones' => 'Aumento de reposición',
        ]);

        $response->assertStatus(201);

        $prod->refresh();
        $this->assertSame(1500.0, (float) $prod->costo);
        // 1500 * (1 + 0.30) = 1950.00
        $this->assertSame(1950.0, (float) $prod->precio_unitario);
    }

    /**
     * Al cambiar el proveedor principal (PV05), el costo se actualiza al nuevo precio acordado.
     */
    public function test_definir_proveedor_principal_sincroniza_costo_reposicion(): void
    {
        $prod = $this->crearProducto([
            'precio_unitario' => 1500.00,
            'costo' => 1000.00,
            'recargo' => 50.00,
        ]);

        $prov1 = $this->crearProveedor();
        $prov2 = $this->crearProveedor();

        $pp1 = ProductoProveedor::create([
            'id_producto' => $prod->id_producto,
            'id_proveedor' => $prov1->id_proveedor,
            'es_proveedor_principal' => true,
            'precio_acordado' => 1000.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->toDateString(),
            'id_usuario' => $this->admin->id,
        ]);

        $pp2 = ProductoProveedor::create([
            'id_producto' => $prod->id_producto,
            'id_proveedor' => $prov2->id_proveedor,
            'es_proveedor_principal' => false,
            'precio_acordado' => 800.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->toDateString(),
            'id_usuario' => $this->admin->id,
        ]);

        // Cambiar principal a prov2
        $response = $this->postJson("/api/proveedores/productos/{$pp2->id_producto_proveedor}/principal");
        $response->assertOk();

        $prod->refresh();
        $this->assertSame(800.0, (float) $prod->costo);
        // 800 * 1.50 = 1200.00
        $this->assertSame(1200.0, (float) $prod->precio_unitario);
    }

    /**
     * Alta y modificación de producto vía API con costo y recargo.
     */
    public function test_store_y_update_producto_con_costo_y_recargo(): void
    {
        $cat = Categoria::first() ?? Categoria::create(['nombre' => 'Cat ' . uniqid(), 'activo' => true]);
        $marca = Marca::first() ?? Marca::create(['nombre' => 'Marca ' . uniqid(), 'activo' => true]);

        // Store
        $storeRes = $this->postJson('/api/productos', [
            'codigo' => 'PROD-STORE-' . uniqid(),
            'nombre' => 'Producto Nuevo Store',
            'descripcion' => 'Descripción nueva',
            'costo' => 2000.00,
            'recargo' => 35.00,
            'id_categoria' => $cat->id_categoria,
            'id_marca' => $marca->id_marca,
            'stock_disponible' => 10,
        ]);

        $storeRes->assertCreated();
        $idProd = (int) ($storeRes->json('data.id_producto') ?? $storeRes->json('data.id'));

        $prod = Producto::find($idProd);
        $this->assertSame(2000.0, (float) $prod->costo);
        $this->assertSame(35.0, (float) $prod->recargo);
        // 2000 * 1.35 = 2700.00
        $this->assertSame(2700.0, (float) $prod->precio_unitario);

        // Update: modificar recargo a 50%
        $updateRes = $this->putJson("/api/productos/{$idProd}", [
            'codigo' => $prod->codigo,
            'nombre' => $prod->nombre,
            'descripcion' => $prod->descripcion,
            'costo' => 2000.00,
            'recargo' => 50.00,
            'id_categoria' => $cat->id_categoria,
            'id_marca' => $marca->id_marca,
        ]);

        $updateRes->assertOk();

        $prod->refresh();
        $this->assertSame(50.0, (float) $prod->recargo);
        // 2000 * 1.50 = 3000.00
        $this->assertSame(3000.0, (float) $prod->precio_unitario);
    }

    /**
     * El contrato con G1 y G4 se preserva: GET /api/productos entrega precio_unitario neto
     * sin exponer costo, recargo ni margen_ganancia hacia otros módulos, mientras que
     * con ?interno=1 los expone para la interfaz interna de G3.
     */
    public function test_contrato_api_productos_mantiene_precio_unitario_para_g1_y_g4(): void
    {
        $prod = $this->crearProducto([
            'precio_unitario' => 2500.00,
            'costo' => 1800.00,
            'recargo' => 38.89,
        ]);

        // 1. Llamada externa (G1 / G2 / G4): no expone costo, recargo ni margen_ganancia
        $res = $this->getJson("/api/productos?codigo={$prod->codigo}");
        $res->assertOk();

        $item = $res->json('data.0');
        $this->assertNotNull($item);
        $this->assertSame($prod->codigo, $item['codigo']);
        $this->assertSame(2500.0, (float) $item['precio_unitario']);
        $this->assertSame(2500.0, (float) $item['precioMay']);
        $this->assertSame(2500.0, (float) $item['precioMin']);
        $this->assertArrayNotHasKey('costo', $item);
        $this->assertArrayNotHasKey('recargo', $item);
        $this->assertArrayNotHasKey('margen_ganancia', $item);

        // 2. Llamada interna G3 (?interno=1): incluye costo, recargo y margen_ganancia
        $resInterna = $this->getJson("/api/productos?codigo={$prod->codigo}&interno=1");
        $resInterna->assertOk();

        $itemInterno = $resInterna->json('data.0');
        $this->assertSame(1800.0, (float) $itemInterno['costo']);
        $this->assertSame(38.89, (float) $itemInterno['recargo']);
        $this->assertArrayHasKey('margen_ganancia', $itemInterno);
    }

    /**
     * Aumento masivo filtrando por proveedor y eligiendo impacto sobre Costo de Reposición
     * (mantiene recargo %) vs impacto sobre Precio de Venta (sube recargo %).
     */
    public function test_aumento_masivo_por_proveedor_sobre_costo_y_sobre_precio(): void
    {
        $provTarget = $this->crearProveedor();
        $provOtro = $this->crearProveedor();

        $prodTarget = $this->crearProducto([
            'costo' => 1000.00,
            'recargo' => 50.00,
            'precio_unitario' => 1500.00,
        ]);

        $prodOtro = $this->crearProducto([
            'costo' => 1000.00,
            'recargo' => 50.00,
            'precio_unitario' => 1500.00,
        ]);

        $asocTarget = ProductoProveedor::create([
            'id_producto' => $prodTarget->id_producto,
            'id_proveedor' => $provTarget->id_proveedor,
            'es_proveedor_principal' => true,
            'precio_acordado' => 1000.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->toDateString(),
            'id_usuario' => $this->admin->id,
        ]);

        ProductoProveedor::create([
            'id_producto' => $prodOtro->id_producto,
            'id_proveedor' => $provOtro->id_proveedor,
            'es_proveedor_principal' => true,
            'precio_acordado' => 1000.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->toDateString(),
            'id_usuario' => $this->admin->id,
        ]);

        // 1. Aumento masivo del 20% por proveedor sobre COSTO:
        // Costo pasa de 1000 a 1200, recargo se mantiene en 50%, precio pasa de 1500 a 1800.
        $resCosto = $this->postJson('/api/productos/aumento-masivo', [
            'id_proveedor' => $provTarget->id_proveedor,
            'base_aumento' => 'costo',
            'porcentaje' => 20.0,
        ]);

        $resCosto->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.base_aumento', 'costo')
            ->assertJsonPath('data.total_aplicados', 1);

        $prodTarget->refresh();
        $prodOtro->refresh();
        $asocTarget->refresh();

        $this->assertSame(1200.0, (float) $prodTarget->costo);
        $this->assertSame(50.0, (float) $prodTarget->recargo);
        $this->assertSame(1800.0, (float) $prodTarget->precio_unitario);
        $this->assertSame(1200.0, (float) $asocTarget->precio_acordado);

        // El producto de otro proveedor no fue afectado
        $this->assertSame(1000.0, (float) $prodOtro->costo);
        $this->assertSame(1500.0, (float) $prodOtro->precio_unitario);

        // 2. Aumento masivo del 10% por proveedor sobre PRECIO:
        // Precio pasa de 1800 a 1980, costo sigue en 1200, recargo sube de 50% a 65%.
        $resPrecio = $this->postJson('/api/productos/aumento-masivo', [
            'id_proveedor' => $provTarget->id_proveedor,
            'base_aumento' => 'precio',
            'porcentaje' => 10.0,
        ]);

        $resPrecio->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.base_aumento', 'precio')
            ->assertJsonPath('data.total_aplicados', 1);

        $prodTarget->refresh();
        $this->assertSame(1200.0, (float) $prodTarget->costo);
        $this->assertSame(1980.0, (float) $prodTarget->precio_unitario);
        $this->assertSame(65.0, (float) $prodTarget->recargo);
    }

    /**
     * Al registrar una Compra en CompraController::store cuyo proveedor es el proveedor principal,
     * se actualiza automáticamente ProductoProveedor.precio_acordado, se sincroniza Producto.costo
     * y se recalcula Producto.precio_unitario aplicando el recargo.
     */
    public function test_registrar_compra_proveedor_principal_actualiza_costo_y_recalcula_precio_venta(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto([
            'costo' => 1000.00,
            'recargo' => 40.00,
            'precio_unitario' => 1400.00,
        ]);

        $asoc = ProductoProveedor::create([
            'id_producto' => $prod->id_producto,
            'id_proveedor' => $prov->id_proveedor,
            'es_proveedor_principal' => true,
            'precio_acordado' => 1000.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->toDateString(),
            'id_usuario' => $this->admin->id,
        ]);

        // Registrar factura de compra con nuevo costo unitario de $1250
        $resCompra = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'fecha_compra' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad' => 10,
                    'precio_unitario' => 1250.00,
                ],
            ],
        ]);

        $resCompra->assertCreated();

        $asoc->refresh();
        $prod->refresh();

        $this->assertSame(1250.0, (float) $asoc->precio_acordado);
        $this->assertSame(1250.0, (float) $prod->costo);
        $this->assertSame(40.0, (float) $prod->recargo);
        // 1250 * 1.40 = 1750.00
        $this->assertSame(1750.0, (float) $prod->precio_unitario);

        $ultimoHistorial = HistorialPrecio::where('id_producto', $prod->id_producto)
            ->orderByDesc('id_historial')
            ->first();

        $this->assertNotNull($ultimoHistorial);
        $this->assertSame('costo_reposicion', $ultimoHistorial->origen);
        $this->assertSame(1750.0, (float) $ultimoHistorial->precio);
    }
}
