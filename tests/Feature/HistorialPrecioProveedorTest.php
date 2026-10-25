<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Compras\Models\Compra;
use App\Modules\Login\Models\User;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\Marca;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\HistorialPrecioProveedor;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Stock\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HistorialPrecioProveedorTest extends TestCase
{
    use DatabaseTransactions, \Tests\UsesG3Database {
        \Tests\UsesG3Database::beginDatabaseTransaction insteadof DatabaseTransactions;
    }

    private function crearProveedorTest(string $cuit = '30-99112233-4'): Proveedor
    {
        $userId = User::first()?->id ?? 1;

        return Proveedor::create([
            'razon_social' => 'Proveedor Test PV06 ' . uniqid(),
            'contacto' => 'Juan Contacto',
            'cuit' => $cuit,
            'telefono' => '1144556677',
            'email' => 'test' . uniqid() . '@proveedor.com',
            'direccion' => 'Av. Siempre Viva 742',
            'estado' => 'activo',
            'fecha_alta' => Carbon::now(),
            'plazo_entrega_dias' => 7,
            'id_usuario_carga' => $userId,
        ]);
    }

    private function crearProductoTest(string $codigo = 'TEST-P01'): Producto
    {
        $cat = Categoria::first() ?? Categoria::create(['nombre' => 'Cat Test', 'activo' => true]);
        $marca = Marca::first() ?? Marca::create(['nombre' => 'Marca Test', 'activo' => true]);
        $userId = User::first()?->id ?? 1;

        return Producto::create([
            'codigo' => $codigo . '-' . uniqid(),
            'nombre' => 'Producto Test ' . uniqid(),
            'descripcion' => 'Descripción Producto Test',
            'id_categoria' => $cat->id_categoria,
            'id_marca' => $marca->id_marca,
            'precio_unitario' => 1000.00,
            'stock' => 50,
            'stock_minimo' => 10,
            'estado' => 'activo',
            'fecha_alta' => Carbon::now(),
            'id_usuario_carga' => $userId,
        ]);
    }

    /**
     * CA-01: Consultar precios históricos de un proveedor sin registros previos.
     */
    public function test_consultar_precios_historicos_proveedor_vacio(): void
    {
        $prov = $this->crearProveedorTest('30-11112222-3');

        $response = $this->getJson("/api/proveedores/{$prov->id_proveedor}/precios-historicos");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'total' => 0,
                'data' => [],
            ])
            ->assertJsonPath('proveedor.id_proveedor', $prov->id_proveedor);
    }

    /**
     * CA-01 y CA-02: Registrar cambio de precio acordado genera automáticamente snapshot en historial.
     */
    public function test_registrar_cambio_precio_genera_historial(): void
    {
        $prov = $this->crearProveedorTest('30-22223333-4');
        $prod = $this->crearProductoTest('PROD-SNAP');

        // Asociar producto con precio inicial de 500
        $asocRes = $this->postJson("/api/proveedores/{$prov->id_proveedor}/productos", [
            'id_productos' => [$prod->id_producto],
            'precio_acordado' => 500.00,
            'es_proveedor_principal' => true,
        ]);
        $asocRes->assertStatus(201);

        // Verificar que se creó el registro inicial en el historial
        $historial1 = $this->getJson("/api/proveedores/{$prov->id_proveedor}/precios-historicos");
        $historial1->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.precio_acordado', 500);

        // Actualizar precio acordado a 650 mediante el endpoint específico de PV06
        $updateRes = $this->postJson("/api/proveedores/{$prov->id_proveedor}/productos/{$prod->id_producto}/precio", [
            'precio_acordado' => 650.00,
        ]);
        $updateRes->assertStatus(201)
            ->assertJsonPath('data.precio_anterior', 500)
            ->assertJsonPath('data.nuevo_precio', 650)
            ->assertJsonPath('data.diferencia_monto', 150)
            ->assertJsonPath('data.aumento_pct', 30);

        // Consultar historial actualizado: debe tener 2 registros
        $historial2 = $this->getJson("/api/proveedores/{$prov->id_proveedor}/precios-historicos");
        $historial2->assertOk()
            ->assertJsonPath('total', 2);
    }

    /**
     * CA-05: Orden cronológico por defecto (DESC) y soporte para alternar a ASC.
     */
    public function test_orden_cronologico_por_defecto_y_ascendente(): void
    {
        $prov = $this->crearProveedorTest('30-33334444-5');
        $prod = $this->crearProductoTest('PROD-ORD');

        $relacion = ProductoProveedor::create([
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'es_proveedor_principal' => true,
            'precio_acordado' => 1200.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->subDays(10),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        // Crear 3 registros en fechas distintas
        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'precio_acordado' => 1000.00,
            'fecha_actualizacion' => Carbon::now()->subDays(5),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'precio_acordado' => 1100.00,
            'fecha_actualizacion' => Carbon::now()->subDays(3),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'precio_acordado' => 1200.00,
            'fecha_actualizacion' => Carbon::now()->subDay(),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        // Por defecto: DESC (el más reciente primero: 1200)
        $resDesc = $this->getJson("/api/proveedores/{$prov->id_proveedor}/precios-historicos");
        $resDesc->assertOk()
            ->assertJsonPath('data.0.precio_acordado', 1200)
            ->assertJsonPath('data.2.precio_acordado', 1000);

        // Solicitando ASC: el más antiguo primero: 1000
        $resAsc = $this->getJson("/api/proveedores/{$prov->id_proveedor}/precios-historicos?orden=asc");
        $resAsc->assertOk()
            ->assertJsonPath('data.0.precio_acordado', 1000)
            ->assertJsonPath('data.2.precio_acordado', 1200);
    }

    /**
     * CA-04: Filtrar por rango de fechas (período).
     */
    public function test_filtrar_por_rango_fechas_periodo(): void
    {
        $prov = $this->crearProveedorTest('30-44445555-6');
        $prod = $this->crearProductoTest('PROD-RANGO');

        $relacion = ProductoProveedor::create([
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'es_proveedor_principal' => true,
            'precio_acordado' => 200.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->subMonths(3),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        // Registro de hace 2 meses
        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'precio_acordado' => 150.00,
            'fecha_actualizacion' => Carbon::now()->subMonths(2),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        // Registro de hace 10 días
        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'precio_acordado' => 180.00,
            'fecha_actualizacion' => Carbon::now()->subDays(10),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        // Registro de hoy
        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'precio_acordado' => 200.00,
            'fecha_actualizacion' => Carbon::now(),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        // Filtrar rango que solo abarque los últimos 15 días hasta ayer
        $desde = Carbon::now()->subDays(15)->format('Y-m-d');
        $hasta = Carbon::now()->subDays(1)->format('Y-m-d');

        $resFiltro = $this->getJson("/api/proveedores/{$prov->id_proveedor}/precios-historicos?fecha_desde={$desde}&fecha_hasta={$hasta}");
        $resFiltro->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.precio_acordado', 180)
            ->assertJsonPath('data.0.precio_anterior', 150)
            ->assertJsonPath('data.0.variacion_porcentual', 20);
    }

    /**
     * CA-04: Cálculo de variación absoluta y porcentual en un aumento.
     */
    public function test_calculo_variacion_porcentual_aumento(): void
    {
        $prov = $this->crearProveedorTest('30-55556666-7');
        $prod = $this->crearProductoTest('PROD-CALC');

        $relacion = ProductoProveedor::create([
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'es_proveedor_principal' => true,
            'precio_acordado' => 1250.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->subDays(2),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'precio_acordado' => 1000.00,
            'fecha_actualizacion' => Carbon::now()->subDays(2),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod->id_producto,
            'precio_acordado' => 1250.00,
            'fecha_actualizacion' => Carbon::now()->subDay(),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        $res = $this->getJson("/api/proveedores/{$prov->id_proveedor}/productos/{$prod->id_producto}/precios-historicos");
        $res->assertOk()
            ->assertJsonPath('estadisticas.precio_inicial', 1000)
            ->assertJsonPath('estadisticas.precio_actual', 1250)
            ->assertJsonPath('estadisticas.variacion_total_monto', 250)
            ->assertJsonPath('estadisticas.variacion_total_pct', 25)
            ->assertJsonPath('historial.0.precio_anterior', 1000)
            ->assertJsonPath('historial.0.diferencia_monto', 250)
            ->assertJsonPath('historial.0.variacion_porcentual', 25);
    }

    /**
     * Validación de proveedor inexistente (HTTP 404).
     */
    public function test_rechazar_proveedor_inexistente(): void
    {
        $response = $this->getJson('/api/proveedores/999999/precios-historicos');
        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'El proveedor no existe.',
            ]);
    }

    /**
     * CA-04: Reporte comparativo de aumentos por proveedor con KPIs.
     */
    public function test_comparativa_aumentos_kpi_resumen(): void
    {
        $prov = $this->crearProveedorTest('30-66667777-8');
        $prod1 = $this->crearProductoTest('PROD-K1');
        $prod2 = $this->crearProductoTest('PROD-K2');

        $rel1 = ProductoProveedor::create([
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod1->id_producto,
            'es_proveedor_principal' => true,
            'precio_acordado' => 200.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->subDays(5),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        $rel2 = ProductoProveedor::create([
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod2->id_producto,
            'es_proveedor_principal' => true,
            'precio_acordado' => 600.00,
            'activo' => true,
            'fecha_asociacion' => Carbon::now()->subDays(5),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        // Prod 1 sube de 100 a 200 (+100%)
        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $rel1->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod1->id_producto,
            'precio_acordado' => 100.00,
            'fecha_actualizacion' => Carbon::now()->subDays(5),
            'id_usuario' => User::first()?->id ?? 1,
        ]);
        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $rel1->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod1->id_producto,
            'precio_acordado' => 200.00,
            'fecha_actualizacion' => Carbon::now()->subDay(),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        // Prod 2 sube de 500 a 600 (+20%)
        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $rel2->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod2->id_producto,
            'precio_acordado' => 500.00,
            'fecha_actualizacion' => Carbon::now()->subDays(5),
            'id_usuario' => User::first()?->id ?? 1,
        ]);
        HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $rel2->id_producto_proveedor,
            'id_proveedor' => $prov->id_proveedor,
            'id_producto' => $prod2->id_producto,
            'precio_acordado' => 600.00,
            'fecha_actualizacion' => Carbon::now()->subDay(),
            'id_usuario' => User::first()?->id ?? 1,
        ]);

        $resComp = $this->getJson("/api/proveedores/{$prov->id_proveedor}/comparativa-aumentos");
        $resComp->assertOk()
            ->assertJsonPath('resumen.total_productos_con_historial', 2)
            ->assertJsonPath('resumen.variacion_promedio_general', fn ($v) => (float) $v == 60.0)
            ->assertJsonPath('resumen.producto_mayor_aumento.id_producto', $prod1->id_producto)
            ->assertJsonPath('resumen.producto_mayor_aumento.variacion_porcentual', fn ($v) => (float) $v == 100.0);
    }

    /**
     * Registro de compra vincula snapshot histórico con id_compra.
     */
    public function test_compra_registra_snapshot_con_id_compra(): void
    {
        $prov = $this->crearProveedorTest('30-77778888-9');
        $prod = $this->crearProductoTest('PROD-COMPRA');

        $compraRes = $this->postJson('/api/compras', [
            'id_proveedor' => $prov->id_proveedor,
            'fecha_compra' => Carbon::now()->format('Y-m-d'),
            'items' => [
                [
                    'id_producto' => $prod->id_producto,
                    'cantidad' => 10,
                    'precio_unitario' => 880.00,
                ],
            ],
        ]);
        $compraRes->assertStatus(201);
        $idCompra = $compraRes->json('data.id_compra');

        $snapshot = HistorialPrecioProveedor::where('id_proveedor', $prov->id_proveedor)
            ->where('id_producto', $prod->id_producto)
            ->where('id_compra', $idCompra)
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertEquals(880.00, (float) $snapshot->precio_acordado);
    }
}
