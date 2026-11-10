<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Compras\Models\Compra;
use App\Modules\Compras\Models\DetalleCompra;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use App\Modules\PedidosDeCompra\Models\DetalleOrden;
use App\Modules\PedidosDeCompra\Models\OrdenCompra;
use App\Modules\PedidosDeCompra\Models\SugerenciaCompra;
use App\Modules\PedidosDeCompra\Services\GeneradorSugerenciasReposicionService;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\Marca;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Stock\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\UsesG3Database;

class CorreccionesLogicaComprasDistribuidoraTest extends TestCase
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
            'codigo' => 'TEST-AUDIT-' . uniqid(),
            'nombre' => 'Producto Auditoria ' . uniqid(),
            'descripcion' => 'Descripción auditoría distribuidora',
            'precio_unitario' => 1500.00,
            'costo' => 1000.00,
            'recargo' => 50.00,
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
            'razon_social' => 'Proveedor Auditoria ' . uniqid(),
            'contacto' => 'Contacto Auditoria',
            'cuit' => $cuit ?? '30-' . rand(10000000, 99999999) . '-1',
            'telefono' => '1155667788',
            'email' => 'audit' . uniqid() . '@test.com',
            'direccion' => 'Av. San Martín 456',
            'estado' => 'activo',
            'fecha_alta' => Carbon::now()->toDateString(),
            'plazo_entrega_dias' => 3,
            'id_usuario_carga' => $this->admin->id,
        ]);
    }

    /**
     * 1. Backend no debe aceptar vincular una orden de compra de otro proveedor,
     * ni una orden cancelada o completada.
     */
    public function test_compra_rechaza_vincular_orden_de_otro_proveedor_o_orden_invalida(): void
    {
        $prov1 = $this->crearProveedor();
        $prov2 = $this->crearProveedor();
        $prod = $this->crearProducto(['costo' => 500.00]);

        // Crear Orden para Prov 1
        $resOrden1 = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $prov1->id_proveedor,
            'items' => [
                ['id_producto' => $prod->id_producto, 'cantidad_solicitada' => 10, 'precio_estimado' => 500.00],
            ],
        ]);
        $resOrden1->assertCreated();
        $idOrden1 = (int) ($resOrden1->json('data.id_orden') ?? $resOrden1->json('data.orden.id_orden'));

        // Intento 1: Crear compra para Prov 2 pero asociando la orden de Prov 1 -> debe fallar con 400
        $resFailProv = $this->postJson('/api/compras', [
            'id_proveedor' => $prov2->id_proveedor,
            'id_orden' => $idOrden1,
            'items' => [
                ['id_producto' => $prod->id_producto, 'cantidad' => 10, 'precio_unitario' => 500.00],
            ],
        ]);
        $resFailProv->assertStatus(400);
        $this->assertStringContainsString('otro proveedor', strtolower($resFailProv->json('message') ?? ''));

        // Intento 2: Orden cancelada no debe poder vincularse a una compra
        $ordenCancelada = OrdenCompra::find($idOrden1);
        $ordenCancelada->estado = 'cancelada';
        $ordenCancelada->save();

        $resFailCancelada = $this->postJson('/api/compras', [
            'id_proveedor' => $prov1->id_proveedor,
            'id_orden' => $idOrden1,
            'items' => [
                ['id_producto' => $prod->id_producto, 'cantidad' => 10, 'precio_unitario' => 500.00],
            ],
        ]);
        $resFailCancelada->assertStatus(400);
        $this->assertStringContainsString('cancelada', strtolower($resFailCancelada->json('message') ?? ''));

        // Intento 3: Orden ya completada no debe poder vincularse a una nueva compra
        $ordenCancelada->estado = 'completada';
        $ordenCancelada->save();

        $resFailCompletada = $this->postJson('/api/compras', [
            'id_proveedor' => $prov1->id_proveedor,
            'id_orden' => $idOrden1,
            'items' => [
                ['id_producto' => $prod->id_producto, 'cantidad' => 10, 'precio_unitario' => 500.00],
            ],
        ]);
        $resFailCompletada->assertStatus(400);
        $this->assertStringContainsString('completada', strtolower($resFailCompletada->json('message') ?? ''));
    }

    /**
     * 2. Una orden cerrada parcialmente de manera definitiva (cierre_parcial = true)
     * no debe reabrirse a 'recibida_parcialmente' si se recalcula su estado de recepción.
     */
    public function test_orden_con_cierre_parcial_no_se_reabre_al_sincronizar_recepcion(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto(['costo' => 1000.00]);

        $resOrden = $this->postJson('/api/ordenes-compra', [
            'id_proveedor' => $prov->id_proveedor,
            'items' => [
                ['id_producto' => $prod->id_producto, 'cantidad_solicitada' => 10, 'precio_estimado' => 1000.00],
            ],
        ]);
        $resOrden->assertCreated();
        $idOrden = (int) ($resOrden->json('data.id_orden') ?? $resOrden->json('data.orden.id_orden'));

        // Crear compra recibiendo parcialmente 4 de 10
        $resCompra = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'id_orden' => $idOrden,
            'recibir_ahora' => true,
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad' => 10,
                    'cantidad_recibida' => 4,
                    'precio_unitario' => 1000.00,
                ],
            ],
        ]);
        $resCompra->assertCreated();
        $idCompra = (int) $resCompra->json('data.id_compra');

        $orden = OrdenCompra::find($idOrden);
        $this->assertSame('recibida_parcialmente', $orden->estado);

        // Se da cierre parcial a la compra (y a la orden vinculada)
        $resCerrar = $this->patchJson("/api/compras/{$idCompra}/cerrar-parcial");
        $resCerrar->assertOk();

        $orden->refresh();
        $this->assertSame('completada', $orden->estado);
        $this->assertTrue((bool) $orden->cierre_parcial);

        // Disparamos sincronizarEstadoRecepcion() manualmente o vía evento
        $orden->sincronizarEstadoRecepcion();
        $orden->refresh();

        // Debe mantenerse COMPLETADA, no reabrirse a 'recibida_parcialmente'
        $this->assertSame('completada', $orden->estado);
    }

    /**
     * 3. Sugerencias de reposición concurrentes para el mismo producto (bajo_stock y agotamiento_inmediato)
     * no deben duplicar cantidades sumándose dos veces en la orden agrupada.
     */
    public function test_sugerencias_reposicion_no_duplican_cantidades_al_agrupar_por_proveedor(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto([
            'costo' => 200.00,
            'stock' => 2,
            'stock_minimo' => 20,
        ]);

        ProductoProveedor::create([
            'id_producto' => $prod->id_producto,
            'id_proveedor' => $prov->id_proveedor,
            'precio_acordado' => 200.00,
            'activo' => true,
            'es_proveedor_principal' => true,
        ]);

        // Crear manualmente 2 sugerencias para el mismo producto y proveedor
        $sug1 = SugerenciaCompra::create([
            'id_producto' => $prod->id_producto,
            'id_proveedor' => $prov->id_proveedor,
            'cantidad_sugerida' => 18,
            'cantidad_minima' => 20,
            'cantidad_maxima' => 20,
            'precio_unitario' => 200.00,
            'costo_total' => 3600.00,
            'velocidad_venta_diaria' => 2.0,
            'plazo_entrega_dias' => 3,
            'fecha_reorden' => Carbon::today()->addDays(3)->toDateString(),
            'estado' => 'pendiente',
            'motivo_generacion' => 'bajo_stock',
            'observaciones' => 'Stock por debajo del mínimo permitido',
        ]);

        $sug2 = SugerenciaCompra::create([
            'id_producto' => $prod->id_producto,
            'id_proveedor' => $prov->id_proveedor,
            'cantidad_sugerida' => 25,
            'cantidad_minima' => 20,
            'cantidad_maxima' => 20,
            'precio_unitario' => 200.00,
            'costo_total' => 5000.00,
            'velocidad_venta_diaria' => 2.0,
            'plazo_entrega_dias' => 3,
            'fecha_reorden' => Carbon::today()->addDays(3)->toDateString(),
            'estado' => 'pendiente',
            'motivo_generacion' => 'agotamiento_inmediato',
            'observaciones' => 'Agotamiento inminente',
        ]);

        $service = app(GeneradorSugerenciasReposicionService::class);
        $res = $service->generarOrdenDesdeSugerencia($sug1->id_sugerencia, $this->admin->id, true);

        $this->assertTrue($res['exito']);
        $idOrden = $res['orden']['id'];

        // En la orden generada, el producto debe tener la cantidad máxima requerida (25), NO la suma (18 + 25 = 43)
        $detalle = DetalleOrden::where('id_orden', $idOrden)->where('id_producto', $prod->id_producto)->first();
        $this->assertNotNull($detalle);
        $this->assertSame(25, (int) $detalle->cantidad_solicitada);

        // Ambas sugerencias deben quedar procesadas
        $this->assertSame('procesada', $sug1->fresh()->estado);
        $this->assertSame('procesada', $sug2->fresh()->estado);
    }

    /**
     * 4. Saldo pendiente de mercadería debe calcularse por el valor monetario real
     * de los ítems pendientes y no por una simple regla de tres de cantidades de unidades.
     */
    public function test_saldo_pendiente_mercaderia_calcula_por_valor_monetario_real_y_no_por_proporcion_unidades(): void
    {
        $prov = $this->crearProveedor();
        // Prod Caro: $10.000 x 1 unidad = $10.000
        $prodCaro = $this->crearProducto(['costo' => 10000.00]);
        // Prod Barato: $100 x 100 unidades = $10.000
        $prodBarato = $this->crearProducto(['costo' => 100.00]);

        // Total compra: $20.000 (101 unidades en total)
        // Se recibe el producto caro (1 de 1) y 0 del producto barato.
        // Faltan 100 unidades del producto barato = exactamente $10.000 de mercadería pendiente.
        // Con la fórmula vieja por unidades (100/101 * $20.000), daba $19.801.98 (erróneo).
        $resCompra = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'recibir_ahora' => true,
            'items' => [
                [
                    'id_producto' => $prodCaro->id_producto,
                    'cantidad' => 1,
                    'cantidad_recibida' => 1,
                    'precio_unitario' => 10000.00,
                ],
                [
                    'id_producto' => $prodBarato->id_producto,
                    'cantidad' => 100,
                    'cantidad_recibida' => 0,
                    'precio_unitario' => 100.00,
                ],
            ],
        ]);
        if ($resCompra->status() !== 201) {
            dump($resCompra->json());
        }
        $resCompra->assertCreated();
        $idCompra = (int) $resCompra->json('data.id_compra');

        // Verificamos que saldo_pendiente sea exactamente 10000.00
        $compra = Compra::find($idCompra);
        $this->assertSame('parcialmente_recibida', $compra->estado);
        $this->assertSame(10000.0, (float) $compra->saldo_pendiente);

        // Ahora probamos lo mismo desde el endpoint de Recepción posterior
        // Creamos otra compra sin recepción inmediata (pendiente)
        $resCompra2 = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'recibir_ahora' => false,
            'items' => [
                [
                    'id_producto' => $prodCaro->id_producto,
                    'cantidad' => 1,
                    'precio_unitario' => 10000.00,
                ],
                [
                    'id_producto' => $prodBarato->id_producto,
                    'cantidad' => 100,
                    'precio_unitario' => 100.00,
                ],
            ],
        ]);
        $resCompra2->assertCreated();
        $idCompra2 = (int) $resCompra2->json('data.id_compra');

        // Recepcionamos el producto caro
        $resRecep = $this->postJson("/api/compras/{$idCompra2}/recepciones", [
            'fecha_recepcion' => Carbon::now()->toDateString(),
            'items' => [
                ['id_producto' => $prodCaro->id_producto, 'cantidad_recibida' => 1],
            ],
        ]);
        $resRecep->assertCreated();

        $compra2 = Compra::find($idCompra2);
        $this->assertSame('parcialmente_recibida', $compra2->estado);
        $this->assertSame(10000.0, (float) $compra2->saldo_pendiente);
    }

    /**
     * 5. Anulaciones: no debe permitir anular compras que ya tengan pagos imputados,
     * ni cuando el stock actual en depósito sea menor al recibido (mercadería ya vendida).
     */
    public function test_cancelar_compra_rechaza_si_posee_pagos_o_stock_insuficiente_para_revertir(): void
    {
        $prov = $this->crearProveedor();
        $prod = $this->crearProducto(['costo' => 500.00, 'stock' => 0]);

        // Caso 1: Compra con pagos no debe anularse
        $resCompra = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'recibir_ahora' => true,
            'items' => [
                ['id_producto' => $prod->id_producto, 'cantidad' => 10, 'cantidad_recibida' => 10, 'precio_unitario' => 500.00],
            ],
        ]);
        if ($resCompra->status() !== 201) {
            dump($resCompra->json());
        }
        $resCompra->assertCreated();
        $idCompra = (int) $resCompra->json('data.id_compra');

        // Registrar un pago parcial
        $resPago = $this->postJson("/api/compras/{$idCompra}/pagos", [
            'monto' => 2000.00,
            'metodo_pago' => 'transferencia',
            'fecha_pago' => Carbon::now()->toDateString(),
        ]);
        $resPago->assertStatus(201);

        // Intentar cancelar la compra -> debe dar 400
        $resCancelFailPago = $this->patchJson("/api/compras/{$idCompra}/cancelar");
        $resCancelFailPago->assertStatus(400);
        $this->assertStringContainsString('pago', strtolower($resCancelFailPago->json('message') ?? ''));

        // Caso 2: Stock consumido / vendido
        // Creamos otra compra sin pagos, recibimos 10 unidades. El stock del producto pasa a 10 + lo anterior.
        $prod2 = $this->crearProducto(['costo' => 500.00, 'stock' => 0]);
        $resCompra2 = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'recibir_ahora' => true,
            'items' => [
                ['id_producto' => $prod2->id_producto, 'cantidad' => 10, 'cantidad_recibida' => 10, 'precio_unitario' => 500.00],
            ],
        ]);
        $resCompra2->assertCreated();
        $idCompra2 = (int) $resCompra2->json('data.id_compra');

        // Simulamos que el módulo de Ventas vendió 8 unidades, dejando solo 2 en stock
        $prod2->stock = 2;
        $prod2->save();

        // Intentar cancelar la compra (necesita revertir 10 unidades de stock pero solo hay 2) -> debe dar 400 descriptivo
        $resCancelFailStock = $this->patchJson("/api/compras/{$idCompra2}/cancelar");
        $resCancelFailStock->assertStatus(400);
        $this->assertStringContainsString('stock', strtolower($resCancelFailStock->json('message') ?? ''));
    }
}
