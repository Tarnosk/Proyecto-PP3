<?php

namespace Tests\Unit\Caja;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Models\CajaMovimiento;
use App\Modules\Caja\Repositories\CajaRepository;
use App\Modules\Caja\Services\CajaService;
use Mockery;
use Tests\TestCase;

/**
 * Medio de pago de los movimientos (efectivo / valores) y reversión de cobros
 * anulados. El repositorio se simula (Mockery).
 */
class CajaMedioPagoReversionTest extends TestCase
{
    private function caja(array $attrs = []): Caja
    {
        $caja = new Caja(array_merge([
            'id_usuario' => 3,
            'monto_inicial' => 1000,
            'estado' => Caja::ESTADO_ABIERTA,
        ], $attrs));
        $caja->id_caja = 7;

        return $caja;
    }

    private function mov(string $tipo, float $monto, string $medio): CajaMovimiento
    {
        return new CajaMovimiento(['tipo' => $tipo, 'monto' => $monto, 'medio_pago' => $medio]);
    }

    public function test_el_medio_de_pago_por_defecto_es_efectivo(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja());
        $repo->shouldReceive('crearMovimiento')->once()->andReturnUsing(function ($d) {
            $this->assertSame('efectivo', $d['medio_pago']);
            return new CajaMovimiento($d);
        });

        $r = (new CajaService($repo))->registrarMovimiento(7, 3, Caja::ROL_REPARTIDOR, 'cobro', 'Cobro', 500.0);

        $this->assertArrayNotHasKey('error', $r);
    }

    public function test_guarda_el_cheque_como_movimiento_con_su_medio(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja());
        $repo->shouldReceive('crearMovimiento')->once()->andReturnUsing(fn ($d) => new CajaMovimiento($d));

        $r = (new CajaService($repo))->registrarMovimiento(
            7, 3, Caja::ROL_REPARTIDOR, 'cobro', 'Cheque cliente', 800.0, null, CajaMovimiento::MEDIO_CHEQUE
        );

        $this->assertSame('cheque', $r['medio_pago']);
    }

    public function test_rechaza_un_medio_de_pago_invalido(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja());
        $repo->shouldReceive('crearMovimiento')->never();

        $r = (new CajaService($repo))->registrarMovimiento(
            7, 3, Caja::ROL_REPARTIDOR, 'cobro', 'Cobro', 100.0, null, 'bitcoin'
        );

        $this->assertTrue($r['error']);
    }

    public function test_cobro_no_efectivo_entra_a_la_caja_abierta_con_su_medio(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->with(3)->andReturn($this->caja());
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja());
        $repo->shouldReceive('crearMovimiento')->once()->andReturnUsing(fn ($d) => new CajaMovimiento($d));

        $r = (new CajaService($repo))->registrarCobro(3, Caja::ROL_REPARTIDOR, 300.0, 'Transferencia', 'transferencia', 55);

        $this->assertSame('transferencia', $r['medio_pago']);
        $this->assertEquals(55, $r['id_cobro']);
    }

    public function test_reversion_se_registra_en_la_caja_abierta_de_quien_anula(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->with(3)->andReturn($this->caja());
        $repo->shouldReceive('crearMovimiento')->once()->andReturnUsing(fn ($d) => new CajaMovimiento($d));

        $r = (new CajaService($repo))->registrarReversionDeCobro(3, Caja::ROL_REPARTIDOR, 500.0, 'Anulación cobro 55', 55);

        $this->assertArrayNotHasKey('error', $r);
        $this->assertSame('reversion', $r['tipo']);
        $this->assertEquals(55, $r['id_cobro']);
        $this->assertEquals(7, $r['id_caja']);
    }

    public function test_reversion_sin_caja_abierta_se_rechaza(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarCualquierAbiertaDeUsuario')->with(3)->andReturn(null);
        $repo->shouldReceive('crearMovimiento')->never();

        $r = (new CajaService($repo))->registrarReversionDeCobro(3, Caja::ROL_REPARTIDOR, 500.0, 'Anulación', 55);

        $this->assertTrue($r['error']);
    }

    public function test_la_reversion_resta_del_saldo_y_no_se_carga_a_mano(): void
    {
        $this->assertFalse($this->mov('reversion', 500, 'efectivo')->esIngreso());
        $this->assertNotContains('reversion', CajaMovimiento::TIPOS_VALIDOS);
        $this->assertNotContains('reversion', CajaMovimiento::tiposPermitidosPara(Caja::ROL_ADMIN));
        $this->assertNotContains('reversion', CajaMovimiento::tiposPermitidosPara(Caja::ROL_REPARTIDOR));
    }

    public function test_los_subtotales_separan_efectivo_y_valores(): void
    {
        $repo = Mockery::mock(CajaRepository::class)->makePartial();
        $repo->shouldReceive('listarMovimientosDeCaja')->with(7)->andReturn(new \Illuminate\Database\Eloquent\Collection([
            $this->mov('cobro', 1000, 'efectivo'),
            $this->mov('egreso', 200, 'efectivo'),
            $this->mov('cobro', 800, 'cheque'),
            $this->mov('reversion', 300, 'cheque'),
        ]));

        $s = $repo->calcularSubtotalesPorMedio(7);

        $this->assertEquals(800, $s['efectivo']);   // 1000 - 200
        $this->assertEquals(500, $s['valores']);    // 800 - 300
    }
}
