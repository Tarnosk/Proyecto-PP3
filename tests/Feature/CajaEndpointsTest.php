<?php

namespace Tests\Feature;

use App\Modules\Caja\Models\Caja;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Permisos y flujos de Caja recorriendo las rutas reales con sesión.
 * SQLite en memoria (ver phpunit.xml): no toca la base configurada en .env.
 *
 * Cajas de prueba (todas cerradas salvo la 4):
 *   1: repartidor 41, cierre manual          2: repartidor 99, cierre manual
 *   3: repartidor 41, cierre automático sin contar     4: administrativo 27, abierta
 */
class CajaEndpointsTest extends TestCase
{
    private const REPARTIDOR = 41;
    private const OTRO_REPARTIDOR = 99;
    private const ADMIN = 27;
    private const CONTADOR = 7;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablas();
        $this->cargarDatos();
    }

    private function sessionAs(UserRole $rol, int $id): void
    {
        $token = 'caja-endpoints-test';
        $user = new User;
        $user->forceFill([
            'id' => $id,
            'nombre' => 'Usuario de prueba',
            'usuario' => 'caja-test-' . $id,
            'rol' => $rol,
            'estado' => UserStatus::ACTIVO,
            'current_session_id' => hash('sha256', $token),
        ]);

        $this->actingAs($user)->withSession(['session_token' => $token, 'last_activity' => now()->timestamp]);
    }

    private function crearTablas(): void
    {
        Schema::create('CAJA', function (Blueprint $t) {
            $t->increments('id_caja');
            $t->unsignedInteger('id_usuario');
            $t->date('fecha');
            $t->decimal('monto_inicial', 12, 2);
            $t->decimal('monto_final', 12, 2)->nullable();
            $t->decimal('monto_esperado', 12, 2)->nullable();
            $t->decimal('diferencia', 12, 2)->nullable();
            $t->boolean('cierre_automatico')->default(false);
            $t->string('estado', 20)->default('abierta');
            $t->unsignedInteger('id_usuario_cierre')->nullable();
            $t->dateTime('fecha_hora_apertura');
            $t->dateTime('fecha_hora_vencimiento')->nullable();
            $t->dateTime('fecha_hora_cierre')->nullable();
            $t->string('observaciones', 500)->nullable();
        });

        Schema::create('CAJA_MOVIMIENTO', function (Blueprint $t) {
            $t->increments('id_movimiento_caja');
            $t->unsignedInteger('id_caja')->nullable();
            $t->date('fecha');
            $t->dateTime('fecha_hora_carga')->nullable();
            $t->decimal('monto', 12, 2);
            $t->string('tipo', 20);
            $t->string('medio_pago', 20)->default('efectivo');
            $t->string('concepto', 255);
            $t->unsignedInteger('id_usuario');
            $t->decimal('monto_anterior', 12, 2)->nullable();
            $t->unsignedInteger('id_usuario_modificacion')->nullable();
            $t->dateTime('fecha_hora_modificacion')->nullable();
            $t->unsignedInteger('id_cobro')->nullable();
        });

        // Directorio de G1: el servicio consulta el rol del dueño de la caja.
        Schema::create('usuarios', function (Blueprint $t) {
            $t->id();
            foreach (['nombre', 'usuario', 'email', 'rol', 'estado'] as $columna) {
                $t->string($columna)->nullable();
            }
        });
    }

    private function cargarDatos(): void
    {
        DB::table('usuarios')->insert([
            ['id' => self::REPARTIDOR, 'nombre' => 'Repartidor', 'rol' => 'repartidor', 'estado' => 'activo'],
            ['id' => self::OTRO_REPARTIDOR, 'nombre' => 'Otro repartidor', 'rol' => 'repartidor', 'estado' => 'activo'],
            ['id' => self::ADMIN, 'nombre' => 'Administración', 'rol' => 'administrativo', 'estado' => 'activo'],
        ]);

        $cerrada = fn (int $id, int $usuario, array $extra = []) => array_merge([
            'id_caja' => $id,
            'id_usuario' => $usuario,
            'fecha' => '2026-11-03',
            'monto_inicial' => 1000,
            'monto_final' => 1500,
            'monto_esperado' => 1500,
            'diferencia' => 0,
            'cierre_automatico' => 0,
            'estado' => 'cerrada',
            'id_usuario_cierre' => $usuario,
            'fecha_hora_apertura' => '2026-11-03 09:00:00',
            'fecha_hora_vencimiento' => '2026-11-06 09:00:00',
            'fecha_hora_cierre' => '2026-11-03 18:00:00',
        ], $extra);

        DB::table('CAJA')->insert([
            $cerrada(1, self::REPARTIDOR),
            $cerrada(2, self::OTRO_REPARTIDOR),
            // Cerrada sola a las 72 h, todavía sin contar.
            $cerrada(3, self::REPARTIDOR, [
                'monto_final' => null,
                'monto_esperado' => 2000,
                'diferencia' => null,
                'cierre_automatico' => 1,
                'id_usuario_cierre' => null,
            ]),
        ]);

        // Caja abierta del administrativo (para probar el cierre).
        // Va en un insert aparte: tiene columnas distintas a las cerradas
        // y SQLite exige las mismas columnas en todas las filas de un insert múltiple.
        DB::table('CAJA')->insert([
            'id_caja' => 4,
            'id_usuario' => self::ADMIN,
            'fecha' => '2026-11-04',
            'monto_inicial' => 500,
            'estado' => 'abierta',
            'fecha_hora_apertura' => '2026-11-04 09:00:00',
            'fecha_hora_vencimiento' => '2026-11-11 09:00:00',
        ]);

        DB::table('CAJA_MOVIMIENTO')->insert([
            'id_caja' => 1,
            'fecha' => '2026-11-03',
            'fecha_hora_carga' => '2026-11-03 10:00:00',
            'monto' => 500,
            'tipo' => 'cobro',
            'medio_pago' => 'efectivo',
            'concepto' => 'Cobro de prueba',
            'id_usuario' => self::REPARTIDOR,
        ]);
    }

    /** Success puede venir envuelto en "data" o directo, según el helper de respuestas. */
    private function lista(TestResponse $respuesta): array
    {
        $cuerpo = $respuesta->json();

        return $cuerpo['data'] ?? $cuerpo;
    }

    private function idsDe(TestResponse $respuesta): array
    {
        $ids = array_map('intval', array_column($this->lista($respuesta), 'id_caja'));
        sort($ids);

        return $ids;
    }

    // --- Lectura de cierres -------------------------------------------------

    public function test_contador_ve_el_historial_de_todos_los_usuarios(): void
    {
        $this->sessionAs(UserRole::CONTADOR, self::CONTADOR);

        $respuesta = $this->getJson('/g2/api/caja/cierres')->assertOk();

        $this->assertSame([1, 2, 3], $this->idsDe($respuesta));
    }

    public function test_repartidor_solo_ve_sus_propios_cierres(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        $respuesta = $this->getJson('/g2/api/caja/cierres')->assertOk();

        $this->assertSame([1, 3], $this->idsDe($respuesta));
    }

    public function test_repartidor_no_ve_el_detalle_del_cierre_de_otro(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        $this->getJson('/g2/api/caja/cierres/2')->assertNotFound();
        $this->getJson('/g2/api/caja/cierres/1')->assertOk();
    }

    public function test_contador_ve_el_detalle_de_cualquier_cierre(): void
    {
        $this->sessionAs(UserRole::CONTADOR, self::CONTADOR);

        $this->getJson('/g2/api/caja/cierres/2')->assertOk();
        $this->getJson('/g2/api/caja/cierres/3')->assertOk();
    }

    // --- El contador no opera la caja --------------------------------------

    public function test_contador_no_puede_operar_la_caja_por_api(): void
    {
        $this->sessionAs(UserRole::CONTADOR, self::CONTADOR);

        $this->postJson('/g2/api/caja/apertura', ['monto_inicial' => 100])->assertForbidden();
        $this->postJson('/g2/api/caja/movimiento', [
            'id_caja' => 4, 'tipo' => 'ingreso_manual', 'concepto' => 'x', 'monto' => 10,
        ])->assertForbidden();
        $this->patchJson('/g2/api/caja/4/cerrar', ['monto_final' => 500])->assertForbidden();
        $this->patchJson('/g2/api/caja/3/completar-conteo', ['monto_final' => 2000])->assertForbidden();

        $this->assertSame('abierta', DB::table('CAJA')->where('id_caja', 4)->value('estado'));
        $this->assertNull(DB::table('CAJA')->where('id_caja', 3)->value('monto_final'));
    }

    public function test_contador_no_puede_operar_la_caja_por_pantalla(): void
    {
        $this->sessionAs(UserRole::CONTADOR, self::CONTADOR);

        $this->post('/caja/apertura', ['monto_inicial' => 100])->assertRedirect(route('dashboard'));
        $this->patch('/caja/4/cerrar', ['monto_final' => 500])->assertRedirect(route('dashboard'));
        $this->get('/caja')->assertRedirect(route('dashboard'));

        $this->assertSame('abierta', DB::table('CAJA')->where('id_caja', 4)->value('estado'));
    }

    // --- Exportación a PDF --------------------------------------------------

    public function test_contador_descarga_los_pdf_del_historial_y_de_un_cierre(): void
    {
        $this->sessionAs(UserRole::CONTADOR, self::CONTADOR);

        $this->get('/caja/cierres/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get('/caja/cierres/1/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
        // También el cierre automático todavía sin contar.
        $this->get('/caja/cierres/3/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_repartidor_no_puede_exportar_pdf(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        $this->get('/caja/cierres/pdf')->assertRedirect(route('dashboard'));
        $this->get('/caja/cierres/1/pdf')->assertRedirect(route('dashboard'));
    }

    public function test_administrativo_ve_todos_los_cierres_y_exporta_pdf(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO, self::ADMIN);

        $respuesta = $this->getJson('/g2/api/caja/cierres')->assertOk();
        $this->assertSame([1, 2, 3], $this->idsDe($respuesta));

        $this->get('/caja/cierres/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    // --- Operación del administrativo ---------------------------------------

    public function test_administrativo_carga_el_conteo_de_un_cierre_automatico(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO, self::ADMIN);

        $this->patchJson('/g2/api/caja/3/completar-conteo', ['monto_final' => 2100])->assertOk();

        $caja = DB::table('CAJA')->where('id_caja', 3)->first();
        $this->assertEquals(2100, (float) $caja->monto_final);
        $this->assertEquals(100, (float) $caja->diferencia); // 2100 contado - 2000 esperado
    }

    public function test_repartidor_no_puede_cargar_el_conteo(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        $this->patchJson('/g2/api/caja/3/completar-conteo', ['monto_final' => 2100])->assertForbidden();

        $this->assertNull(DB::table('CAJA')->where('id_caja', 3)->value('monto_final'));
    }

    // --- Tope de montos -----------------------------------------------------

    public function test_un_monto_pasado_de_rango_devuelve_validacion_y_no_un_error_500(): void
    {
        $this->sessionAs(UserRole::ADMINISTRATIVO, self::ADMIN);

        // El caso real que reventó la columna decimal(12,2).
        $this->patchJson('/g2/api/caja/4/cerrar', ['monto_final' => 15331800000])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['monto_final']);

        $this->assertSame('abierta', DB::table('CAJA')->where('id_caja', 4)->value('estado'));
        $this->assertStringContainsString('99.999.999,99', Caja::MENSAJE_MONTO_MAXIMO);
    }
}
