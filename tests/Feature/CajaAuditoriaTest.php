<?php

namespace Tests\Feature;

use App\Modules\Caja\Services\CajaService;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Auditoría de Caja: cada operación que mueve plata deja una fila en
 * log_auditoria (tabla compartida, ver App\Support\AuditoriaLogger).
 * SQLite en memoria (ver phpunit.xml): no toca la base configurada en .env.
 */
class CajaAuditoriaTest extends TestCase
{
    private const REPARTIDOR = 41;
    private const OTRO_REPARTIDOR = 99;
    private const ADMIN = 27;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crearTablas();
        $this->cargarUsuarios();
    }

    private function sessionAs(UserRole $rol, int $id): void
    {
        $token = 'caja-auditoria-test';
        $user = new User;
        $user->forceFill([
            'id' => $id,
            'nombre' => 'Usuario de prueba',
            'usuario' => 'caja-audit-' . $id,
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

        Schema::create('usuarios', function (Blueprint $t) {
            $t->id();
            foreach (['nombre', 'usuario', 'email', 'rol', 'estado'] as $columna) {
                $t->string($columna)->nullable();
            }
        });

        // Igual a la de G3 (sin la clave foránea, que SQLite no necesita acá).
        Schema::create('log_auditoria', function (Blueprint $t) {
            $t->increments('id_auditoria');
            $t->string('tabla_afectada', 80);
            $t->unsignedInteger('id_registro');
            $t->string('accion', 10);
            $t->text('valores_anteriores')->nullable();
            $t->text('valores_nuevos')->nullable();
            $t->unsignedInteger('id_usuario')->nullable();
            $t->dateTime('fecha_hora');
        });
    }

    private function cargarUsuarios(): void
    {
        DB::table('usuarios')->insert(['id' => self::REPARTIDOR, 'nombre' => 'Repartidor', 'rol' => 'repartidor', 'estado' => 'activo']);
        DB::table('usuarios')->insert(['id' => self::OTRO_REPARTIDOR, 'nombre' => 'Otro repartidor', 'rol' => 'repartidor', 'estado' => 'activo']);
        DB::table('usuarios')->insert(['id' => self::ADMIN, 'nombre' => 'Administración', 'rol' => 'administrativo', 'estado' => 'activo']);
    }

    private function insertarCaja(int $id, int $usuario, array $extra = []): void
    {
        DB::table('CAJA')->insert(array_merge([
            'id_caja' => $id,
            'id_usuario' => $usuario,
            'fecha' => '2026-11-04',
            'monto_inicial' => 1000,
            'estado' => 'abierta',
            'cierre_automatico' => 0,
            'fecha_hora_apertura' => '2026-11-04 09:00:00',
            'fecha_hora_vencimiento' => '2026-11-07 09:00:00',
        ], $extra));
    }

    private function ultimoLog(string $tabla): object
    {
        $fila = DB::table('log_auditoria')
            ->where('tabla_afectada', $tabla)
            ->orderByDesc('id_auditoria')
            ->first();

        $this->assertNotNull($fila, "No se registró auditoría para {$tabla}.");

        return $fila;
    }

    private function valores(?string $json): array
    {
        return json_decode($json ?? '[]', true) ?? [];
    }

    // --- Cajas ---------------------------------------------------------------

    public function test_abrir_caja_registra_un_insert(): void
    {
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        $this->postJson('/g2/api/caja/apertura', ['monto_inicial' => 1000])->assertCreated();

        $log = $this->ultimoLog('CAJA');
        $nuevos = $this->valores($log->valores_nuevos);

        $this->assertSame('INSERT', $log->accion);
        $this->assertSame((int) DB::table('CAJA')->value('id_caja'), (int) $log->id_registro);
        $this->assertSame(self::REPARTIDOR, (int) $log->id_usuario);
        $this->assertNull($log->valores_anteriores);
        $this->assertEquals(1000, $nuevos['monto_inicial']);
        $this->assertSame('abierta', $nuevos['estado']);
    }

    public function test_cierre_manual_registra_el_antes_y_el_despues(): void
    {
        $this->insertarCaja(10, self::REPARTIDOR);
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        $this->patchJson('/g2/api/caja/10/cerrar', ['monto_final' => 1000])->assertOk();

        $log = $this->ultimoLog('CAJA');
        $antes = $this->valores($log->valores_anteriores);
        $despues = $this->valores($log->valores_nuevos);

        $this->assertSame('UPDATE', $log->accion);
        $this->assertSame(10, (int) $log->id_registro);
        $this->assertSame(self::REPARTIDOR, (int) $log->id_usuario);
        $this->assertSame('abierta', $antes['estado']);
        $this->assertSame('cerrada', $despues['estado']);
        $this->assertEquals(1000, $despues['monto_final']);
        $this->assertFalse($despues['cierre_automatico']);
    }

    public function test_cierre_automatico_se_registra_a_nombre_del_sistema(): void
    {
        $this->insertarCaja(20, self::REPARTIDOR, ['fecha_hora_vencimiento' => '2020-01-01 00:00:00']);

        $cerradas = app(CajaService::class)->cerrarCajasVencidas();

        $log = $this->ultimoLog('CAJA');
        $despues = $this->valores($log->valores_nuevos);

        $this->assertSame(1, $cerradas);
        $this->assertSame('UPDATE', $log->accion);
        $this->assertSame(20, (int) $log->id_registro);
        $this->assertNull($log->id_usuario); // se muestra como "Sistema"
        $this->assertTrue($despues['cierre_automatico']);
        $this->assertNull($despues['monto_final']);
    }

    public function test_cargar_el_conteo_de_un_cierre_automatico_se_registra(): void
    {
        $this->insertarCaja(30, self::REPARTIDOR, [
            'estado' => 'cerrada',
            'cierre_automatico' => 1,
            'monto_esperado' => 2000,
            'fecha_hora_cierre' => '2026-11-07 09:00:00',
        ]);
        $this->sessionAs(UserRole::ADMINISTRATIVO, self::ADMIN);

        $this->patchJson('/g2/api/caja/30/completar-conteo', ['monto_final' => 2100])->assertOk();

        $log = $this->ultimoLog('CAJA');
        $antes = $this->valores($log->valores_anteriores);
        $despues = $this->valores($log->valores_nuevos);

        $this->assertSame('UPDATE', $log->accion);
        $this->assertSame(self::ADMIN, (int) $log->id_usuario);
        $this->assertNull($antes['monto_final']);
        $this->assertEquals(2100, $despues['monto_final']);
        $this->assertEquals(100, $despues['diferencia']);
    }

    // --- Movimientos ---------------------------------------------------------

    public function test_registrar_un_movimiento_se_audita(): void
    {
        $this->insertarCaja(10, self::REPARTIDOR);
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        $this->postJson('/g2/api/caja/movimiento', [
            'id_caja' => 10, 'tipo' => 'cobro', 'concepto' => 'Cobro de prueba', 'monto' => 250,
        ])->assertCreated();

        $log = $this->ultimoLog('CAJA_MOVIMIENTO');
        $nuevos = $this->valores($log->valores_nuevos);

        $this->assertSame('INSERT', $log->accion);
        $this->assertSame((int) DB::table('CAJA_MOVIMIENTO')->value('id_movimiento_caja'), (int) $log->id_registro);
        $this->assertSame(self::REPARTIDOR, (int) $log->id_usuario);
        $this->assertSame('cobro', $nuevos['tipo']);
        $this->assertEquals(250, $nuevos['monto']);
    }

    public function test_modificar_un_movimiento_guarda_monto_anterior_y_nuevo(): void
    {
        $this->insertarCaja(10, self::REPARTIDOR);
        DB::table('CAJA_MOVIMIENTO')->insert([
            'id_movimiento_caja' => 1,
            'id_caja' => 10,
            'fecha' => '2026-11-04',
            'fecha_hora_carga' => '2026-11-04 10:00:00',
            'monto' => 250,
            'tipo' => 'cobro',
            'medio_pago' => 'efectivo',
            'concepto' => 'Cobro original',
            'id_usuario' => self::REPARTIDOR,
        ]);

        app(CajaService::class)->modificarMovimiento(1, 'Cobro corregido', 300.0, self::ADMIN);

        $log = $this->ultimoLog('CAJA_MOVIMIENTO');
        $antes = $this->valores($log->valores_anteriores);
        $despues = $this->valores($log->valores_nuevos);

        $this->assertSame('UPDATE', $log->accion);
        $this->assertSame(1, (int) $log->id_registro);
        $this->assertSame(self::ADMIN, (int) $log->id_usuario);
        $this->assertEquals(250, $antes['monto']);
        $this->assertEquals(300, $despues['monto']);
        $this->assertSame('Cobro corregido', $despues['concepto']);
    }

    // --- Garantías -----------------------------------------------------------

    public function test_una_operacion_rechazada_no_deja_registro(): void
    {
        $this->insertarCaja(10, self::OTRO_REPARTIDOR);
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        // No puede cerrar la caja de otro repartidor.
        $this->patchJson('/g2/api/caja/10/cerrar', ['monto_final' => 1000])->assertStatus(409);

        $this->assertSame(0, DB::table('log_auditoria')->count());
        $this->assertSame('abierta', DB::table('CAJA')->where('id_caja', 10)->value('estado'));
    }

    public function test_si_falla_la_auditoria_la_operacion_de_caja_sigue_andando(): void
    {
        Schema::drop('log_auditoria');
        $this->insertarCaja(10, self::REPARTIDOR);
        $this->sessionAs(UserRole::REPARTIDOR, self::REPARTIDOR);

        $this->patchJson('/g2/api/caja/10/cerrar', ['monto_final' => 1000])->assertOk();

        $this->assertSame('cerrada', DB::table('CAJA')->where('id_caja', 10)->value('estado'));
    }
}
