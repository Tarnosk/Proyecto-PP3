<?php

namespace Tests\Unit\Caja;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Models\CajaMovimiento;
use App\Modules\Caja\Repositories\CajaRepository;
use App\Modules\Caja\Services\CajaService;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

/**
 * Tests de las reglas del circuito de cajas. El repositorio se simula
 * (Mockery), así que no tocan la base de datos.
 */
class CajaReglasCircuitoTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow(); // restaura el reloj real
        parent::tearDown();
    }

    private function crearCaja(array $atributos = []): Caja
    {
        $caja = new Caja(array_merge([
            'id_usuario' => 1,
            'monto_inicial' => 1000,
            'estado' => Caja::ESTADO_ABIERTA,
        ], $atributos));
        $caja->id_caja = 7;

        return $caja;
    }

    /** Repo simulado sin caja abierta; captura los datos que se pasan a crear(). */
    private function repoSinCajaAbierta(?array &$capturado): CajaRepository
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->andReturn(null);
        $repo->shouldReceive('crear')->once()->andReturnUsing(function ($datos) use (&$capturado) {
            $capturado = $datos;
            return new Caja($datos);
        });

        return $repo;
    }

    // ---------- Vencimiento ----------

    public function test_repartidor_vence_a_las_72_horas(): void
    {
        Carbon::setTestNow('2026-10-28 10:00:00');
        $capturado = null;

        (new CajaService($this->repoSinCajaAbierta($capturado)))->abrirCaja(1, 'repartidor', 1000.0);

        $this->assertSame('2026-10-31 10:00:00', $capturado['fecha_hora_vencimiento']->toDateTimeString());
    }

    public function test_admin_vence_a_los_7_dias_si_cae_antes_de_fin_de_mes(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $capturado = null;

        (new CajaService($this->repoSinCajaAbierta($capturado)))->abrirCaja(2, 'admin', 0.0);

        $this->assertSame('2026-10-17 10:00:00', $capturado['fecha_hora_vencimiento']->toDateTimeString());
    }

    public function test_admin_vence_a_fin_de_mes_si_los_7_dias_lo_superan(): void
    {
        Carbon::setTestNow('2026-10-28 10:00:00'); // +7 días sería el 4/11
        $capturado = null;

        (new CajaService($this->repoSinCajaAbierta($capturado)))->abrirCaja(2, 'admin', 0.0);

        $this->assertSame('2026-10-31 23:59:59', $capturado['fecha_hora_vencimiento']->toDateTimeString());
    }

    // ---------- Una abierta por usuario ----------

    public function test_no_permite_abrir_si_ya_hay_una_abierta(): void
    {
        Carbon::setTestNow('2026-10-11 09:00:00');

        $existente = $this->crearCaja([
            'fecha_hora_apertura' => Carbon::parse('2026-10-10 10:00:00'),
            'fecha_hora_vencimiento' => Carbon::parse('2026-10-13 10:00:00'),
        ]);

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->with(1)->andReturn($existente);
        $repo->shouldReceive('crear')->never();

        $r = (new CajaService($repo))->abrirCaja(1, 'repartidor', 1000.0);

        $this->assertTrue($r['error']);
        $this->assertSame(7, $r['id_caja_pendiente']);
        $this->assertFalse($r['vencida']);
    }

    public function test_avisa_que_la_caja_abierta_esta_vencida(): void
    {
        Carbon::setTestNow('2026-10-15 09:00:00');

        $existente = $this->crearCaja([
            'fecha_hora_apertura' => Carbon::parse('2026-10-10 10:00:00'),
            'fecha_hora_vencimiento' => Carbon::parse('2026-10-13 10:00:00'),
        ]);

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->with(1)->andReturn($existente);

        $r = (new CajaService($repo))->abrirCaja(1, 'repartidor', 1000.0);

        $this->assertTrue($r['vencida']);
        $this->assertStringContainsString('ya venció', $r['mensaje']);
    }

    // ---------- Tipos de movimiento por rol ----------

    public function test_repartidor_no_puede_registrar_impuesto(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->crearCaja());
        $repo->shouldReceive('crearMovimiento')->never();

        $r = (new CajaService($repo))->registrarMovimiento(
            7, 1, 'repartidor', CajaMovimiento::TIPO_IMPUESTO, 'Impuesto', 100.0
        );

        $this->assertTrue($r['error']);
    }

    public function test_admin_puede_registrar_impuesto_y_se_guarda_la_hora_de_carga(): void
    {
        Carbon::setTestNow('2026-10-15 12:30:00');
        $capturado = null;

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->crearCaja(['id_usuario' => 2]));
        $repo->shouldReceive('crearMovimiento')->once()->andReturnUsing(function ($datos) use (&$capturado) {
            $capturado = $datos;
            return new CajaMovimiento($datos);
        });

        $r = (new CajaService($repo))->registrarMovimiento(
            7, 2, 'admin', CajaMovimiento::TIPO_IMPUESTO, 'Impuesto', 100.0
        );

        $this->assertArrayNotHasKey('error', $r);
        $this->assertSame('2026-10-15 12:30:00', $capturado['fecha_hora_carga']->toDateTimeString());
    }

    public function test_no_permite_movimientos_en_caja_de_otro_usuario(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->crearCaja(['id_usuario' => 2]));

        $r = (new CajaService($repo))->registrarMovimiento(
            7, 1, 'repartidor', CajaMovimiento::TIPO_COBRO, 'Cobro', 100.0
        );

        $this->assertTrue($r['error']);
    }

    public function test_no_permite_movimientos_en_caja_cerrada(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)
            ->andReturn($this->crearCaja(['estado' => Caja::ESTADO_CERRADA]));

        $r = (new CajaService($repo))->registrarMovimiento(
            7, 1, 'repartidor', CajaMovimiento::TIPO_COBRO, 'Cobro', 100.0
        );

        $this->assertTrue($r['error']);
    }

    // ---------- Cierre y diferencia ----------

    public function test_cierre_sin_diferencia(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->crearCaja());
        $repo->shouldReceive('calcularSaldoMovimientos')->with(7)->andReturn(300.0); // +500 cobro -200 egreso
        $repo->shouldReceive('guardar')->once()->andReturnUsing(fn ($caja) => $caja);

        $r = (new CajaService($repo))->cerrarCaja(7, 1, 1300.0); // esperado: 1000 + 300

        $this->assertEquals(0, $r['diferencia']);
        $this->assertSame(Caja::ESTADO_CERRADA, $r['estado']);
    }

    public function test_cierre_con_faltante_registra_diferencia_negativa(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->crearCaja());
        $repo->shouldReceive('calcularSaldoMovimientos')->with(7)->andReturn(300.0);
        $repo->shouldReceive('guardar')->once()->andReturnUsing(fn ($caja) => $caja);

        $r = (new CajaService($repo))->cerrarCaja(7, 1, 1250.0);

        $this->assertEquals(-50, $r['diferencia']);
    }

    public function test_no_permite_cerrar_la_caja_de_otro_usuario(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->crearCaja(['id_usuario' => 2]));
        $repo->shouldReceive('guardar')->never();

        $r = (new CajaService($repo))->cerrarCaja(7, 1, 1000.0);

        $this->assertTrue($r['error']);
    }

    // ---------- Modelo: qué suma y qué resta ----------

    public function test_solo_cobro_e_ingreso_manual_suman_al_saldo(): void
    {
        foreach ([CajaMovimiento::TIPO_COBRO, CajaMovimiento::TIPO_INGRESO_MANUAL] as $tipo) {
            $this->assertTrue((new CajaMovimiento(['tipo' => $tipo]))->esIngreso(), $tipo);
        }

        foreach ([
            CajaMovimiento::TIPO_EGRESO,
            CajaMovimiento::TIPO_EXTRACCION,
            CajaMovimiento::TIPO_PAGO_PROVEEDOR,
            CajaMovimiento::TIPO_IMPUESTO,
        ] as $tipo) {
            $this->assertFalse((new CajaMovimiento(['tipo' => $tipo]))->esIngreso(), $tipo);
        }
    }

    public function test_repartidor_solo_tiene_cobro_y_egreso(): void
    {
        $this->assertSame(
            [CajaMovimiento::TIPO_COBRO, CajaMovimiento::TIPO_EGRESO],
            CajaMovimiento::tiposPermitidosPara('repartidor')
        );
        $this->assertContains(CajaMovimiento::TIPO_IMPUESTO, CajaMovimiento::tiposPermitidosPara('admin'));
    }
}