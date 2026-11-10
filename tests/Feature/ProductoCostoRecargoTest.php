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

    /**
     * Editar el precio acordado de la asociación en Proveedores (PUT /api/proveedores/productos/{id})
     * sincroniza automáticamente el costo del producto y recalcula su precio de venta.
     */
    public function test_editar_asociacion_proveedor_actualiza_costo_y_recalcula_precio_venta(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto([
            'costo' => 1000.00,
            'recargo' => 50.00,
            'precio_unitario' => 1500.00,
        ]);

        // 1. Asociar desde POST /api/proveedores/{id}/productos (primer proveedor -> queda como principal)
        $resAsoc = $this->postJson("/api/proveedores/{$prov->id_proveedor}/productos", [
            'id_productos' => [$prod->id_producto],
            'precio_acordado' => 1100.00,
        ]);
        $resAsoc->assertStatus(201);

        $prod->refresh();
        $this->assertSame(1100.0, (float) $prod->costo);
        // 1100 * 1.50 = 1650.00
        $this->assertSame(1650.0, (float) $prod->precio_unitario);

        $asoc = ProductoProveedor::where('id_producto', $prod->id_producto)
            ->where('id_proveedor', $prov->id_proveedor)
            ->firstOrFail();

        // 2. Actualizar precio acordado desde el modal de edición (PUT /api/proveedores/productos/{id})
        $resEdit = $this->putJson("/api/proveedores/productos/{$asoc->id_producto_proveedor}", [
            'precio_acordado' => 1400.00,
            'es_proveedor_principal' => true,
        ]);
        $resEdit->assertOk();

        $prod->refresh();
        $this->assertSame(1400.0, (float) $prod->costo);
        // 1400 * 1.50 = 2100.00
        $this->assertSame(2100.0, (float) $prod->precio_unitario);
    }

    /**
     * Verificar enlace bidireccional entre Orden de Compra y Compra, y que al registrar un pago
     * en la compra se actualicen total_pagado y saldo_a_pagar en el listado general de Compras.
     */
    public function test_enlace_orden_compra_y_actualizacion_saldo_a_pagar_en_listado_compras(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto([
            'costo' => 1000.00,
            'recargo' => 30.00,
            'precio_unitario' => 1300.00,
        ]);

        // 1. Crear Orden de Compra en estado pendiente
        $resOrden = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $prov->id_proveedor,
            'fecha_creacion' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad_solicitada' => 10,
                    'precio_estimado' => 1000.00,
                ],
            ],
        ]);
        $resOrden->assertCreated();
        $idOrden = (int) $resOrden->json('data.id_orden');
        $numeroOrden = $resOrden->json('data.numero_orden');

        // 2. Registrar Compra vinculada a la Orden de Compra
        $resCompra = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'id_orden' => $idOrden,
            'fecha_compra' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad' => 10,
                    'precio_unitario' => 1000.00,
                ],
            ],
        ]);
        $resCompra->assertCreated();
        $idCompra = (int) $resCompra->json('data.id_compra');
        $numeroCompra = $resCompra->json('data.numero_compra');

        // La compra devuelve el enlace a la orden y su saldo_a_pagar inicial ($10.000)
        $this->assertSame($idOrden, (int) $resCompra->json('data.id_orden'));
        $this->assertSame($numeroOrden, $resCompra->json('data.numero_orden'));
        $this->assertSame(10000.0, (float) $resCompra->json('data.saldo_a_pagar'));
        $this->assertSame(0.0, (float) $resCompra->json('data.total_pagado'));

        // La orden pasó automáticamente de 'pendiente' a 'enviada' y expone la compra vinculada
        $resShowOrden = $this->getJson("/api/ordenes-compra/{$idOrden}");
        $resShowOrden->assertOk();
        $this->assertSame('enviada', $resShowOrden->json('data.orden.estado'));
        $this->assertSame($idCompra, (int) $resShowOrden->json('data.orden.id_compra'));
        $this->assertSame($numeroCompra, $resShowOrden->json('data.orden.numero_compra'));

        // 3. Registrar un pago parcial de $4.000 sobre la compra
        $resPago = $this->postJson("/api/compras/{$idCompra}/pagos", [
            'metodo_pago' => 'transferencia',
            'importe' => 4000.00,
            'fecha_pago' => Carbon::now()->toDateString(),
        ]);
        $resPago->assertCreated();

        // 4. Consultar el listado general de compras (GET /api/compras) y verificar que saldo_a_pagar bajó a $6.000
        $resListado = $this->getJson("/api/compras?id_proveedor={$prov->id_proveedor}");
        $resListado->assertOk();
        $compraEnTabla = collect($resListado->json('data'))->firstWhere('id_compra', $idCompra);

        $this->assertNotNull($compraEnTabla);
        $this->assertSame(4000.0, (float) $compraEnTabla['total_pagado']);
        $this->assertSame(6000.0, (float) $compraEnTabla['saldo_a_pagar']);
        $this->assertSame('pago_parcial', $compraEnTabla['estado_pago']);
    }

    /**
     * Flujo real de distribuidora:
     * 1) Compra con recibir_ahora=true ingresa stock en un solo paso.
     * 2) Entregas parciales (1 Orden de 100 u. -> Compra #1 por 60 u. deja Orden en recibida_parcialmente con 40 u. pendientes -> Compra #2 por 40 u. pasa Orden a completada).
     * 3) Anular Compra #2 revierte automáticamente las 40 u. de stock y devuelve la Orden a recibida_parcialmente.
     */
    public function test_flujo_distribuidora_recepcion_inmediata_entregas_parciales_y_anulacion_con_reversion_stock(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto([
            'stock' => 20,
            'stock_minimo' => 50,
            'costo' => 1000.00,
            'recargo' => 40.00,
            'precio_unitario' => 1400.00,
        ]);

        // 1. Emitir Orden de Compra por 100 unidades
        $resOrden = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $prov->id_proveedor,
            'fecha_creacion' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad_solicitada' => 100,
                    'precio_estimado' => 1000.00,
                ],
            ],
        ]);
        $resOrden->assertCreated();
        $idOrden = (int) $resOrden->json('data.id_orden');

        // 2. Llega el primer camión con Factura #1 por 60 unidades (recibir_ahora = true)
        $resCompra1 = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'id_orden' => $idOrden,
            'recibir_ahora' => true,
            'numero_comprobante' => 'A-0001-00000101',
            'fecha_compra' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad' => 60,
                    'precio_unitario' => 1100.00,
                ],
            ],
        ]);
        $resCompra1->assertCreated();
        $idCompra1 = (int) $resCompra1->json('data.id_compra');

        // Stock subió en el acto de 20 a 80 sin paso extra de recepción
        $prod->refresh();
        $this->assertSame(80, (int) $prod->stock);
        $this->assertSame('completada', $resCompra1->json('data.estado'));
        $this->assertSame('pendiente_pago', $resCompra1->json('data.estado_pago'));

        // La Orden de Compra (100 u.) quedó en 'recibida_parcialmente' con 60 recibidas y 40 pendientes
        $resShowOrden1 = $this->getJson("/api/ordenes-compra/{$idOrden}");
        $resShowOrden1->assertOk();
        $this->assertSame('recibida_parcialmente', $resShowOrden1->json('data.orden.estado'));
        $this->assertSame(60, (int) $resShowOrden1->json('data.total_recibido'));
        $this->assertSame(40, (int) $resShowOrden1->json('data.total_pendiente'));

        // 3. Llega el segundo camión con Factura #2 por las 40 unidades restantes (recibir_ahora = true)
        $resCompra2 = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'id_orden' => $idOrden,
            'recibir_ahora' => true,
            'numero_comprobante' => 'A-0001-00000102',
            'fecha_compra' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad' => 40,
                    'precio_unitario' => 1100.00,
                ],
            ],
        ]);
        $resCompra2->assertCreated();
        $idCompra2 = (int) $resCompra2->json('data.id_compra');

        // Stock subió de 80 a 120 y la Orden de Compra pasó a 'completada' (0 pendientes)
        $prod->refresh();
        $this->assertSame(120, (int) $prod->stock);

        $resShowOrden2 = $this->getJson("/api/ordenes-compra/{$idOrden}");
        $resShowOrden2->assertOk();
        $this->assertSame('completada', $resShowOrden2->json('data.orden.estado'));
        $this->assertSame(100, (int) $resShowOrden2->json('data.total_recibido'));
        $this->assertSame(0, (int) $resShowOrden2->json('data.total_pendiente'));
        $this->assertCount(2, $resShowOrden2->json('data.compras'));

        // 4. Si se anula la Compra #2, se revierten las 40 u. de stock (vuelve a 80) y la Orden vuelve a 'recibida_parcialmente'
        $resCancel2 = $this->patchJson("/api/compras/{$idCompra2}/cancelar");
        $resCancel2->assertOk();
        $this->assertSame('cancelada', $resCancel2->json('data.estado'));

        $prod->refresh();
        $this->assertSame(80, (int) $prod->stock);

        $resShowOrden3 = $this->getJson("/api/ordenes-compra/{$idOrden}");
        $resShowOrden3->assertOk();
        $this->assertSame('recibida_parcialmente', $resShowOrden3->json('data.orden.estado'));
        $this->assertSame(60, (int) $resShowOrden3->json('data.total_recibido'));
        $this->assertSame(40, (int) $resShowOrden3->json('data.total_pendiente'));

        // 5. Mientras tiene una compra activa (#1), no se puede cancelar la orden, pero sí se puede dar por completada
        $resCancelConCompras = $this->patchJson("/api/ordenes-compra/{$idOrden}/cancelar");
        $resCancelConCompras->assertStatus(400);

        // Si en cambio anulamos también la Compra #1, el stock vuelve a 20 y la orden vuelve a 'enviada' sin compras activas
        $resCancel1 = $this->patchJson("/api/compras/{$idCompra1}/cancelar");
        $resCancel1->assertOk();
        $prod->refresh();
        $this->assertSame(20, (int) $prod->stock);

        $resShowOrden4 = $this->getJson("/api/ordenes-compra/{$idOrden}");
        $resShowOrden4->assertOk();
        $this->assertSame('enviada', $resShowOrden4->json('data.orden.estado'));
        $this->assertCount(0, $resShowOrden4->json('data.compras'));

        // Ahora sí podemos cancelar la Orden de Compra aunque haya estado enviada
        $resCancelOrden = $this->patchJson("/api/ordenes-compra/{$idOrden}/cancelar");
        $resCancelOrden->assertOk();
        $this->assertSame('cancelada', $resCancelOrden->json('data.estado'));

        // 6. Sugerencias de reposición: prod tiene stock = 20 y stock_minimo = 50 -> debe sugerir exactamente 30 u. (50 - 20)
        $resGenSug = $this->postJson('/api/sugerencias-reposicion/generar');
        $resGenSug->assertStatus(201);

        $resListSug = $this->getJson("/api/sugerencias-reposicion?id_proveedor={$prov->id_proveedor}");
        $resListSug->assertOk();
        $sugProd = collect($resListSug->json('datos'))->firstWhere('id_producto', $prod->id_producto);
        $this->assertNotNull($sugProd);
        $this->assertSame(30, (int) $sugProd['cantidad_sugerida']);
    }

    /**
     * Prueba que dar por completada una compra parcialmente recibida
     * recalcula el importe total y la deuda al stock efectivamente recibido,
     * y da por completada la orden vinculada.
     */
    public function test_cerrar_compra_parcial_recalcula_deuda_y_completa_orden(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto([
            'stock' => 10,
            'stock_minimo' => 20,
            'costo' => 1000.00,
            'recargo' => 40.00,
            'precio_unitario' => 1400.00,
        ]);

        // 1. Emitir Orden por 100 u.
        $resOrden = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $prov->id_proveedor,
            'fecha_creacion' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad_solicitada' => 100,
                    'precio_estimado' => 1000.00,
                ],
            ],
        ]);
        $resOrden->assertCreated();
        $idOrden = (int) $resOrden->json('data.id_orden');

        // 2. Registrar Compra vinculada por 100 u. sin recibir aún (importe = $100.000)
        $resCompra = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'id_orden' => $idOrden,
            'recibir_ahora' => false,
            'numero_comprobante' => 'FC-0001-00000999',
            'fecha_compra' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad' => 100,
                    'precio_unitario' => 1000.00,
                ],
            ],
        ]);
        $resCompra->assertCreated();
        $idCompra = (int) $resCompra->json('data.id_compra');

        // Deuda inicial: $100.000
        $resDeudas1 = $this->getJson("/api/compras/deudas?id_proveedor={$prov->id_proveedor}");
        $resDeudas1->assertOk();
        $compraDeuda1 = collect($resDeudas1->json('data.compras_pendientes'))->firstWhere('id_compra', $idCompra);
        $this->assertNotNull($compraDeuda1);
        $this->assertSame(100000.0, (float) $compraDeuda1['saldo_a_pagar']);

        // 3. Recepción parcial de 60 u.
        $resRecep = $this->postJson("/api/compras/{$idCompra}/recepciones", [
            'fecha_recepcion' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad_recibida' => 60,
                ],
            ],
        ]);
        $resRecep->assertCreated();

        // 4. Se cierra la compra con lo recibido (PATCH /api/compras/{id}/cerrar-parcial)
        $resCerrar = $this->patchJson("/api/compras/{$idCompra}/cerrar-parcial");
        $resCerrar->assertOk();
        $this->assertSame('completada', $resCerrar->json('data.estado'));
        $this->assertSame(60000.0, (float) $resCerrar->json('data.importe_total'));
        $this->assertSame(60000.0, (float) $resCerrar->json('data.saldo_a_pagar'));

        // 5. La deuda en el listado de proveedores bajó inmediatamente a $60.000
        $resDeudas2 = $this->getJson("/api/compras/deudas?id_proveedor={$prov->id_proveedor}");
        $resDeudas2->assertOk();
        $compraDeuda2 = collect($resDeudas2->json('data.compras_pendientes'))->firstWhere('id_compra', $idCompra);
        $this->assertNotNull($compraDeuda2);
        $this->assertSame(60000.0, (float) $compraDeuda2['saldo_a_pagar']);

        // 6. La orden de compra vinculada pasó a 'completada' con 0 pendiente
        $resShowOrden = $this->getJson("/api/ordenes-compra/{$idOrden}");
        $resShowOrden->assertOk();
        $this->assertSame('completada', $resShowOrden->json('data.orden.estado'));
        $this->assertSame(0, (int) $resShowOrden->json('data.orden.total_pendiente'));
        $this->assertSame(0, (int) $resShowOrden->json('data.total_pendiente'));
        $this->assertTrue((bool) $resCerrar->json('data.cierre_parcial'));
        $this->assertTrue((bool) $resShowOrden->json('data.orden.cierre_parcial'));
    }

    public function test_recepcion_flexible_en_creacion_de_compra_y_auto_avance_de_orden_pendiente(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto([
            'stock' => 10,
            'costo' => 800.00,
            'recargo' => 50.00,
            'precio_unitario' => 1200.00,
        ]);

        // 1. Crear Orden en estado 'pendiente'
        $resOrden = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $prov->id_proveedor,
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad_solicitada' => 100,
                    'precio_estimado' => 800.00,
                ],
            ],
        ]);
        $resOrden->assertCreated();
        $idOrden = (int) $resOrden->json('data.id_orden');
        $this->assertSame('pendiente', $resOrden->json('data.estado'));

        // 2. Registrar Compra vinculada con recepción flexible (facturadas 100, recibidas físicamente hoy 70)
        $resCompra = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'id_orden' => $idOrden,
            'recibir_ahora' => true,
            'numero_comprobante' => 'FC-0001-00008888',
            'fecha_compra' => Carbon::now()->toDateString(),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad' => 100,
                    'cantidad_recibida' => 70,
                    'precio_unitario' => 800.00,
                ],
            ],
        ]);
        $resCompra->assertCreated();
        $idCompra = (int) $resCompra->json('data.id_compra');

        // La compra nace como parcialmente_recibida
        $this->assertSame('parcialmente_recibida', $resCompra->json('data.estado'));
        $this->assertSame(80000.0, (float) $resCompra->json('data.importe_total'));
        // Saldo pendiente de mercadería (30 u. * $800 = $24.000)
        $this->assertSame(24000.0, (float) $resCompra->json('data.saldo_pendiente'));

        // El stock físico del producto aumentó exactamente por 70 (10 inicial + 70 = 80)
        $prod->refresh();
        $this->assertSame(80, (int) $prod->stock_actual);

        // La orden de compra asociada dejó de estar en 'pendiente' y avanzó a 'recibida_parcialmente'
        $resOrdenShow = $this->getJson("/api/ordenes-compra/{$idOrden}");
        $resOrdenShow->assertOk();
        $this->assertSame('recibida_parcialmente', $resOrdenShow->json('data.orden.estado'));
        $this->assertSame(70, (int) $resOrdenShow->json('data.total_recibido'));
        $this->assertSame(0, (int) $resOrdenShow->json('data.total_pendiente'));
        $this->assertSame(30, (int) $resOrdenShow->json('data.total_falta_recibir'));
        $this->assertSame(30, (int) $resOrdenShow->json('data.detalles.0.falta_recibir'));
    }
}
