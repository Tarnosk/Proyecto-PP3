<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tests de la API del módulo Ventas (app/Modules/Ventas/routes/api.php):
 *   POST /api/ventas       → alta (VentaController@store, de develop-g4)
 *   GET  /api/ventas       → lista (VentaApiController@index)
 *   GET  /api/ventas/{id}  → una venta (VentaApiController@show)
 *
 * Modificar, anular, restaurar y cambiar estado están en tests/Feature/Ventas/.
 */
class VentasApiTest extends TestCase
{
    use RefreshDatabase;

    // Cliente válido de App\Services\MockClienteApi.
    private const CLIENTE = 915736;

    // Archivo temporal donde escribe el canal de log "ventas" durante el test.
    private string $archivoLog;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('venta', function (Blueprint $table): void {
            $table->increments('id_venta');
            $table->unsignedInteger('id_cliente');
            $table->date('fecha');
            $table->decimal('total', 10, 2);
            $table->decimal('descuento_global', 10, 2)->default(0);
            $table->string('numFactura', 50);
            $table->string('estado');
            $table->string('observaciones', 150);
            $table->unsignedInteger('id_usuario');
        });

        Schema::create('detalle_venta', function (Blueprint $table): void {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_venta');
            $table->unsignedInteger('id_producto');
            $table->unsignedInteger('id_promocion')->nullable();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('descuento', 10, 2);
            $table->decimal('subtotal', 10, 2);
        });

        // El log de ventas va a un archivo temporal para no ensuciar storage/logs/ventas.
        $this->archivoLog = tempnam(sys_get_temp_dir(), 'ventas_test_');
        config(['logging.channels.ventas' => ['driver' => 'single', 'path' => $this->archivoLog]]);
    }

    protected function tearDown(): void
    {
        @unlink($this->archivoLog);

        parent::tearDown();
    }

    /**
     * Venta mínima válida; $cambios pisa o agrega campos.
     */
    private function venta(array $cambios = []): array
    {
        return array_replace([
            'cliente_id' => self::CLIENTE,
            'lista' => 'minorista',
            'items' => [
                ['id' => 1, 'cantidad' => 1, 'descuento' => 0],
            ],
        ], $cambios);
    }

    private function log(): string
    {
        return (string) file_get_contents($this->archivoLog);
    }

    private function assertNadaGuardado(): void
    {
        $this->assertDatabaseCount('venta', 0);
        $this->assertDatabaseCount('detalle_venta', 0);
        $this->assertDatabaseCount('cuenta_corriente_movimientos', 0);
    }

    // =====================================================================
    // GET /api/ventas
    // =====================================================================

    public function test_index_sin_ventas_devuelve_lista_vacia(): void
    {
        $this->getJson('/api/ventas')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_index_lista_ventas_mas_nueva_primero_con_detalles(): void
    {
        $this->postJson('/api/ventas', $this->venta())->assertCreated();
        $this->postJson('/api/ventas', $this->venta([
            'items' => [
                ['id' => 2, 'cantidad' => 1, 'descuento' => 0],
                ['id' => 3, 'cantidad' => 1, 'descuento' => 0],
            ],
        ]))->assertCreated();

        $this->getJson('/api/ventas')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 2)
            ->assertJsonPath('data.1.id', 1)
            ->assertJsonCount(2, 'data.0.detalles')
            ->assertJsonCount(1, 'data.1.detalles');
    }

    // =====================================================================
    // GET /api/ventas/{id}
    // =====================================================================

    public function test_show_devuelve_la_venta_con_sus_detalles(): void
    {
        $this->postJson('/api/ventas', $this->venta())->assertCreated();

        $this->getJson('/api/ventas/1')
            ->assertOk()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.cliente.id', self::CLIENTE)
            ->assertJsonPath('data.fecha', now()->toDateString())
            ->assertJsonPath('data.total', 2500)
            ->assertJsonPath('data.detalles.0.id_producto', 1)
            ->assertJsonPath('data.detalles.0.cantidad', 1);
    }

    public function test_show_venta_inexistente_devuelve_404(): void
    {
        $this->getJson('/api/ventas/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'La venta #999 no existe.', 'codigo' => 'VENTA_NO_ENCONTRADA', 'venta_id' => 999]);
    }

    public function test_show_id_no_numerico_no_matchea_la_ruta(): void
    {
        $this->getJson('/api/ventas/abc')->assertNotFound();
    }

    public function test_productos_simulados_no_es_capturado_por_la_ruta_show(): void
    {
        $this->getJson('/api/ventas/productos-simulados')
            ->assertOk()
            ->assertJsonCount(6)
            ->assertJsonPath('0.nombre', 'Coca-Cola 1.5L');
    }

    // =====================================================================
    // POST /api/ventas - casos exitosos
    // =====================================================================

    public function test_store_registra_venta_detalles_y_cuenta_corriente(): void
    {
        $respuesta = $this->postJson('/api/ventas', $this->venta([
            'observaciones' => 'Entrega el martes',
            'items' => [
                ['id' => 1, 'cantidad' => 2, 'descuento' => 100],  // 2 x (2500 - 100) = 4800
                ['id' => 6, 'cantidad' => 3, 'descuento' => 0],    // 3 x 950         = 2850
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('mensaje', 'Venta registrada y cargada a la cuenta corriente.')
            ->assertJsonPath('venta_id', 1)
            ->assertJsonPath('cliente_id', self::CLIENTE)
            ->assertJsonPath('descuento_global', 0)
            ->assertJsonPath('total', 7650);

        // El alta no devuelve la venta completa: se consulta con GET /api/ventas/{id}.
        $this->getJson('/api/ventas/'.$respuesta->json('venta_id'))
            ->assertJsonPath('data.estado.valor', 'pendiente')
            ->assertJsonPath('data.observaciones', 'Entrega el martes')
            ->assertJsonCount(2, 'data.detalles');

        $this->assertMatchesRegularExpression('/^POS-[0-9A-Z]{26}$/', DB::table('venta')->value('numFactura'));

        $this->assertDatabaseHas('venta', [
            'id_venta' => 1,
            'id_cliente' => self::CLIENTE,
            'fecha' => now()->toDateString(),
            'total' => 7650,
            'descuento_global' => 0,
            'estado' => 'pendiente',
            'id_usuario' => config('cuenta_corriente.id_usuario_prueba'),
        ]);
        $this->assertDatabaseHas('detalle_venta', [
            'id_venta' => 1, 'id_producto' => 1, 'id_promocion' => null,
            'cantidad' => 2, 'precio_unitario' => 2500, 'descuento' => 100, 'subtotal' => 4800,
        ]);
        $this->assertDatabaseHas('detalle_venta', [
            'id_venta' => 1, 'id_producto' => 6,
            'cantidad' => 3, 'precio_unitario' => 950, 'descuento' => 0, 'subtotal' => 2850,
        ]);
        $this->assertDatabaseHas('cuenta_corriente_movimientos', [
            'cliente_id' => self::CLIENTE,
            'venta_id' => 1,
            'tipo' => 'venta',
            'descripcion' => 'Venta POS #1',
            'importe' => -7650,
        ]);
    }

    public function test_store_usa_precio_mayorista(): void
    {
        $this->postJson('/api/ventas', $this->venta([
            'lista' => 'mayorista',
            'items' => [['id' => 3, 'cantidad' => 2, 'descuento' => 0]],
        ]))
            ->assertCreated()
            ->assertJsonPath('total', 7800); // 2 x 3900

        $this->assertDatabaseHas('detalle_venta', ['id_producto' => 3, 'precio_unitario' => 3900]);
    }

    public function test_store_ignora_precios_y_totales_enviados_por_el_front(): void
    {
        $this->postJson('/api/ventas', $this->venta([
            'total' => 1,
            'items' => [['id' => 1, 'cantidad' => 1, 'descuento' => 0, 'precio' => 1, 'subtotal' => 1]],
        ]))
            ->assertCreated()
            ->assertJsonPath('total', 2500);

        $this->assertDatabaseHas('detalle_venta', ['precio_unitario' => 2500, 'subtotal' => 2500]);
    }

    public function test_store_observaciones_vacias_usan_texto_por_defecto(): void
    {
        $this->postJson('/api/ventas', $this->venta(['observaciones' => '   ']))->assertCreated();
        $this->postJson('/api/ventas', $this->venta())->assertCreated();

        $this->assertSame(2, DB::table('venta')->where('observaciones', 'Venta POS a cuenta corriente')->count());
    }

    public function test_store_observaciones_se_guardan_sin_espacios_extremos(): void
    {
        $this->postJson('/api/ventas', $this->venta(['observaciones' => '  Hola  ']))->assertCreated();

        $this->assertDatabaseHas('venta', ['observaciones' => 'Hola']);
    }

    public function test_store_descuento_global_porcentaje(): void
    {
        $this->postJson('/api/ventas', $this->venta([
            'descuento_global' => ['modo' => 'porcentaje', 'valor' => 15],
            'items' => [['id' => 2, 'cantidad' => 2, 'descuento' => 0]], // 3600
        ]))
            ->assertCreated()
            ->assertJsonPath('descuento_global', 540)
            ->assertJsonPath('total', 3060);

        $this->assertDatabaseHas('venta', ['descuento_global' => 540, 'total' => 3060]);
        $this->assertDatabaseHas('cuenta_corriente_movimientos', ['importe' => -3060]);
    }

    public function test_store_descuento_global_monto_fijo(): void
    {
        $this->postJson('/api/ventas', $this->venta([
            'descuento_global' => ['modo' => 'monto', 'valor' => 500.5],
        ]))
            ->assertCreated()
            ->assertJsonPath('descuento_global', 500.5)
            ->assertJsonPath('total', 1999.5);
    }

    public function test_store_descuento_global_porcentaje_decimal_redondea_a_centavos(): void
    {
        $this->postJson('/api/ventas', $this->venta([
            'descuento_global' => ['modo' => 'porcentaje', 'valor' => 33.333],
            'items' => [['id' => 6, 'cantidad' => 1, 'descuento' => 0]], // 950
        ]))
            ->assertCreated()
            ->assertJsonPath('descuento_global', 316.66)
            ->assertJsonPath('total', 633.34);
    }

    public function test_store_descuento_por_item_igual_al_precio_se_permite_si_el_total_es_positivo(): void
    {
        $this->postJson('/api/ventas', $this->venta([
            'items' => [
                ['id' => 1, 'cantidad' => 1, 'descuento' => 2500], // regalo
                ['id' => 6, 'cantidad' => 1, 'descuento' => 0],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('total', 950);
    }

    public function test_store_mismo_producto_repetido_genera_dos_lineas(): void
    {
        $this->postJson('/api/ventas', $this->venta([
            'items' => [
                ['id' => 1, 'cantidad' => 1, 'descuento' => 0],
                ['id' => 1, 'cantidad' => 2, 'descuento' => 0],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('total', 7500);

        $this->assertDatabaseCount('detalle_venta', 2);
    }

    public function test_store_acepta_cien_items(): void
    {
        $items = array_fill(0, 100, ['id' => 6, 'cantidad' => 1, 'descuento' => 0]);

        $this->postJson('/api/ventas', $this->venta(['items' => $items]))
            ->assertCreated()
            ->assertJsonPath('total', 95000);

        $this->assertDatabaseCount('detalle_venta', 100);
    }

    public function test_store_cada_venta_tiene_numero_de_factura_unico(): void
    {
        $this->postJson('/api/ventas', $this->venta())->assertCreated();
        $this->postJson('/api/ventas', $this->venta())->assertCreated();

        $this->assertSame(2, DB::table('venta')->distinct()->count('numFactura'));
    }

    // =====================================================================
    // POST /api/ventas - validación (422, no guarda nada)
    // =====================================================================

    #[DataProvider('datosInvalidos')]
    public function test_store_rechaza_datos_invalidos(array $cambios, string $campoConError): void
    {
        $this->postJson('/api/ventas', $this->venta($cambios))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($campoConError);

        $this->assertNadaGuardado();
    }

    public static function datosInvalidos(): array
    {
        $item = fn (array $c) => ['items' => [array_replace(['id' => 1, 'cantidad' => 1, 'descuento' => 0], $c)]];

        return [
            'cliente_id faltante' => [['cliente_id' => null], 'cliente_id'],
            'cliente_id no numérico' => [['cliente_id' => 'abc'], 'cliente_id'],
            'cliente inexistente' => [['cliente_id' => 999999], 'cliente_id'],
            'lista faltante' => [['lista' => null], 'lista'],
            'lista inválida' => [['lista' => 'vip'], 'lista'],
            'observaciones > 150' => [['observaciones' => str_repeat('a', 151)], 'observaciones'],
            'observaciones no string' => [['observaciones' => ['x']], 'observaciones'],
            'items faltante' => [['items' => null], 'items'],
            'items vacío' => [['items' => []], 'items'],
            'items no array' => [['items' => 'x'], 'items'],
            'items > 100' => [['items' => array_fill(0, 101, ['id' => 1, 'cantidad' => 1, 'descuento' => 0])], 'items'],
            'item sin id' => [['items' => [['cantidad' => 1, 'descuento' => 0]]], 'items.0.id'],
            'item id no entero' => [$item(['id' => 'x']), 'items.0.id'],
            'producto inexistente' => [$item(['id' => 99]), 'items.0.id'],
            'cantidad 0' => [$item(['cantidad' => 0]), 'items.0.cantidad'],
            'cantidad negativa' => [$item(['cantidad' => -1]), 'items.0.cantidad'],
            'cantidad decimal' => [$item(['cantidad' => 1.5]), 'items.0.cantidad'],
            'cantidad > 9999' => [$item(['cantidad' => 10000]), 'items.0.cantidad'],
            'descuento faltante' => [['items' => [['id' => 1, 'cantidad' => 1]]], 'items.0.descuento'],
            'descuento negativo' => [$item(['descuento' => -1]), 'items.0.descuento'],
            'descuento > precio' => [$item(['descuento' => 2500.01]), 'items.0.descuento'],
            'descuento_global no array' => [['descuento_global' => 10], 'descuento_global'],
            'descuento_global modo inválido' => [['descuento_global' => ['modo' => 'x', 'valor' => 1]], 'descuento_global.modo'],
            'descuento_global sin modo' => [['descuento_global' => ['valor' => 1]], 'descuento_global.modo'],
            'descuento_global sin valor' => [['descuento_global' => ['modo' => 'monto']], 'descuento_global.valor'],
            'descuento_global valor negativo' => [['descuento_global' => ['modo' => 'monto', 'valor' => -5]], 'descuento_global.valor'],
            'porcentaje > 100' => [['descuento_global' => ['modo' => 'porcentaje', 'valor' => 100.01]], 'descuento_global.valor'],
            'porcentaje 100 deja total 0' => [['descuento_global' => ['modo' => 'porcentaje', 'valor' => 100]], 'items'],
            'monto >= subtotal deja total 0' => [['descuento_global' => ['modo' => 'monto', 'valor' => 99999]], 'items'],
            'descuento por item deja total 0' => [$item(['descuento' => 2500]), 'items'],
        ];
    }

    public function test_store_error_en_segundo_item_indica_su_indice(): void
    {
        $this->postJson('/api/ventas', $this->venta([
            'items' => [
                ['id' => 1, 'cantidad' => 1, 'descuento' => 0],
                ['id' => 77, 'cantidad' => 1, 'descuento' => 0],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.1.id');

        $this->assertNadaGuardado();
    }

    public function test_store_descuento_valida_contra_precio_de_la_lista_elegida(): void
    {
        // 2300 es válido en minorista (2500) pero supera el mayorista (2200).
        $this->postJson('/api/ventas', $this->venta([
            'lista' => 'mayorista',
            'items' => [['id' => 1, 'cantidad' => 1, 'descuento' => 2300]],
        ]))->assertJsonValidationErrors('items.0.descuento');

        $this->postJson('/api/ventas', $this->venta([
            'lista' => 'minorista',
            'items' => [['id' => 1, 'cantidad' => 1, 'descuento' => 2300]],
        ]))->assertCreated()->assertJsonPath('total', 200);
    }

    public function test_store_descuento_global_array_vacio_no_rompe(): void
    {
        $this->postJson('/api/ventas', $this->venta(['descuento_global' => []]))
            ->assertStatus(201);
    }

    // =====================================================================
    // POST /api/ventas - error de base de datos (500 + rollback)
    // El alta es VentaController@store (develop-g4): no tiene try/catch propio,
    // así que el 500 lo arma Laravel. Lo importante: no queda nada a medias.
    // =====================================================================

    public function test_store_falla_de_bd_devuelve_500_y_deshace_todo(): void
    {
        config(['app.debug' => false]);
        Schema::drop('cuenta_corriente_movimientos'); // falla el último insert de la transacción

        $this->postJson('/api/ventas', $this->venta())
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Server Error']);

        $this->assertDatabaseCount('venta', 0);
        $this->assertDatabaseCount('detalle_venta', 0);
    }

    public function test_store_falla_de_bd_no_muestra_detalles_internos_sin_debug(): void
    {
        config(['app.debug' => false]);
        Schema::drop('cuenta_corriente_movimientos');

        $respuesta = $this->postJson('/api/ventas', $this->venta())->assertStatus(500);

        $this->assertStringNotContainsString('cuenta_corriente_movimientos', $respuesta->getContent());
    }

    // =====================================================================
    // Log de ventas (VentaLogger)
    // El alta todavía no escribe log (es código de develop-g4). Estos tests
    // cubren el log de las operaciones nuevas; el detalle de cada una está
    // en tests/Feature/Ventas/.
    // =====================================================================

    public function test_log_operacion_exitosa(): void
    {
        $id = $this->postJson('/api/ventas', $this->venta())->assertCreated()->json('venta_id');

        $this->patchJson("/api/ventas/{$id}/estado", ['estado' => 'preparada'])->assertOk();

        $log = $this->log();
        $this->assertStringContainsString('.INFO:', $log);
        $this->assertStringContainsString("Endpoint ......: PATCH /api/ventas/{$id}/estado", $log);
        $this->assertStringContainsString('Ruta definida en: app/Modules/Ventas/routes/api.php', $log);
        $this->assertStringContainsString('ESTADO CAMBIADO  ·  Respuesta HTTP 200 OK', $log);
    }

    public function test_log_operacion_rechazada_indica_el_paso(): void
    {
        $id = $this->postJson('/api/ventas', $this->venta())->assertCreated()->json('venta_id');

        $this->deleteJson("/api/ventas/{$id}", ['motivo' => 'abc'])->assertUnprocessable();

        $log = $this->log();
        $this->assertStringContainsString('.WARNING:', $log);
        $this->assertStringContainsString('Falló en ......: PASO 1 - Validación del formato de los datos', $log);
        $this->assertStringContainsString('El motivo tiene que tener al menos 5 caracteres.', $log);
    }

    public function test_log_error_de_bd_indica_el_paso_y_la_linea(): void
    {
        $id = $this->postJson('/api/ventas', $this->venta())->assertCreated()->json('venta_id');
        Schema::drop('cuenta_corriente_movimientos');

        $this->deleteJson("/api/ventas/{$id}", ['motivo' => 'El cliente se arrepintió'])->assertStatus(500);

        $log = $this->log();
        $this->assertStringContainsString('.ERROR:', $log);
        $this->assertStringContainsString('Falló en ......: PASO 4 - Corregir la cuenta corriente', $log);
        $this->assertStringContainsString('En nuestro código: app/Modules/Ventas/Services/VentaService.php:', $log);
    }
}
