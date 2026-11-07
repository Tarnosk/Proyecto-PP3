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
 * Tests de las correcciones de Caja: vendedor sin caja, movimientos con caja
 * vencida, redondeo de la diferencia, auditoría al modificar un movimiento y
 * cobro en efectivo hacia la caja abierta. El repositorio se simula (Mockery).
 */
class CajaCorreccionesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function caja(array $attrs = []): Caja
    {
        $caja = new Caja(array_merge([
            'id_usuario' => 1,
            'monto_inicial' => 1000,
            'estado' => Caja::ESTADO_ABIERTA,
        ], $attrs));
        $caja->id_caja = 7;

        return $caja;
    }

    // ---------- Vendedor ----------

    public function test_vendedor_no_puede_abrir_caja(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('crear')->never();

        $r = (new CajaService($repo))->abrirCaja(3, 'vendedor', 100.0);

        $this->assertTrue($r['error']);
    }

    public function test_vendedor_no_tiene_tipos_de_movimiento(): void
    {
        $this->assertSame([], CajaMovimiento::tiposPermitidosPara('vendedor'));
    }

    // ---------- Apertura y movimientos ----------

    public function test_permite_abrir_otra_caja_el_mismo_dia_si_la_anterior_esta_cerrada(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->andReturn(null);
        $repo->shouldReceive('crear')->once()->andReturnUsing(fn ($d) => new Caja($d));

        $r = (new CajaService($repo))->abrirCaja(1, 'repartidor', 500.0);

        $this->assertArrayNotHasKey('error', $r);
    }

    public function test_permite_movimientos_con_la_caja_vencida(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $vencida = $this->caja(['fecha_hora_vencimiento' => Carbon::parse('2026-10-15 10:00:00')]);

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($vencida);
        $repo->shouldReceive('crearMovimiento')->once()->andReturnUsing(fn ($d) => new CajaMovimiento($d));

        $r = (new CajaService($repo))->registrarMovimiento(7, 1, 'repartidor', CajaMovimiento::TIPO_COBRO, 'Cobro', 100.0);

        $this->assertArrayNotHasKey('error', $r);
    }

    // ---------- Cierre ----------

    public function test_diferencia_se_redondea_a_dos_decimales(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja(['monto_inicial' => 100.10]));
        $repo->shouldReceive('calcularSaldoMovimientos')->with(7)->andReturn(0.20);
        $repo->shouldReceive('guardar')->once()->andReturnUsing(fn ($c) => $c);

        $r = (new CajaService($repo))->cerrarCaja(7, 1, 100.30);

        $this->assertEquals(0, $r['diferencia']);
        $this->assertEquals(100.30, $r['monto_esperado']);
    }

    // ---------- Modificar movimiento ----------

    public function test_no_permite_modificar_movimiento_de_caja_cerrada(): void
    {
        $mov = new CajaMovimiento(['id_caja' => 7, 'tipo' => 'cobro', 'monto' => 10, 'concepto' => 'X']);

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarMovimientoPorId')->andReturn($mov);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja(['estado' => Caja::ESTADO_CERRADA]));
        $repo->shouldReceive('guardarMovimiento')->never();

        $r = (new CajaService($repo))->modificarMovimiento(1, 'Y', 20.0, 2);

        $this->assertTrue($r['error']);
    }

    public function test_modificar_movimiento_guarda_monto_anterior_y_quien_lo_cambio(): void
    {
        Carbon::setTestNow('2026-10-20 11:00:00');
        $mov = new CajaMovimiento(['id_caja' => 7, 'tipo' => 'cobro', 'monto' => 10, 'concepto' => 'X']);
        $guardado = null;

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarMovimientoPorId')->andReturn($mov);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja());
        $repo->shouldReceive('guardarMovimiento')->once()->andReturnUsing(function ($m) use (&$guardado) {
            $guardado = $m;
            return $m;
        });

        (new CajaService($repo))->modificarMovimiento(1, 'Y', 20.0, 2);

        $this->assertEquals(10, $guardado->monto_anterior);
        $this->assertEquals(20, $guardado->monto);
        $this->assertSame('Y', $guardado->concepto);
        $this->assertEquals(2, $guardado->id_usuario_modificacion);
        $this->assertSame('2026-10-20 11:00:00', $guardado->fecha_hora_modificacion->toDateTimeString());
    }

    // ---------- Cobro en efectivo ----------

    public function test_cobro_en_efectivo_sin_caja_abierta_se_rechaza(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->with(1)->andReturn(null);
        $repo->shouldReceive('crearMovimiento')->never();

        $r = (new CajaService($repo))->registrarCobroEnEfectivo(1, 'repartidor', 500.0, 'Cobro #5', 5);

        $this->assertTrue($r['error']);
    }

    public function test_cobro_en_efectivo_entra_a_la_caja_abierta_como_tipo_cobro(): void
    {
        $capturado = null;

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->with(1)->andReturn($this->caja());
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja());
        $repo->shouldReceive('crearMovimiento')->once()->andReturnUsing(function ($d) use (&$capturado) {
            $capturado = $d;
            return new CajaMovimiento($d);
        });

        (new CajaService($repo))->registrarCobroEnEfectivo(1, 'repartidor', 500.0, 'Cobro #5', 5);

        $this->assertSame(CajaMovimiento::TIPO_COBRO, $capturado['tipo']);
        $this->assertSame(7, $capturado['id_caja']);
        $this->assertSame(5, $capturado['id_cobro']);
    }
}