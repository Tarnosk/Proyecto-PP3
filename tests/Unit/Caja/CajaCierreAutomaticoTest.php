<?php

namespace Tests\Unit\Caja;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Repositories\CajaRepository;
use App\Modules\Caja\Services\CajaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

/**
 * Cierre automático a las 72 h (solo cajas de repartidor): queda sin contar,
 * con el monto esperado guardado y sin monto final ni diferencia. Después se
 * carga el conteo. El repositorio se simula (Mockery).
 */
class CajaCierreAutomaticoTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

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

    private function cajaCerradaSola(array $attrs = []): Caja
    {
        return $this->caja(array_merge([
            'estado' => Caja::ESTADO_CERRADA,
            'cierre_automatico' => true,
            'monto_esperado' => 1300,
        ], $attrs));
    }

    // ---------- Cierre automático ----------

    public function test_cierra_sin_contar_las_cajas_vencidas(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');
        $guardada = null;

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('listarVencidasDeRepartidores')->once()->andReturn(new Collection([$this->caja()]));
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja());
        $repo->shouldReceive('calcularSaldoMovimientos')->with(7)->andReturn(300.0);
        $repo->shouldReceive('guardar')->once()->andReturnUsing(function ($c) use (&$guardada) {
            $guardada = $c;
            return $c;
        });

        $cantidad = (new CajaService($repo))->cerrarCajasVencidas();

        $this->assertSame(1, $cantidad);
        $this->assertSame(Caja::ESTADO_CERRADA, $guardada->estado);
        $this->assertTrue($guardada->cierre_automatico);
        $this->assertEquals(1300, $guardada->monto_esperado);   // 1000 + 300
        $this->assertNull($guardada->monto_final);               // sin contar
        $this->assertNull($guardada->diferencia);
        $this->assertNull($guardada->id_usuario_cierre);
        $this->assertSame('2026-10-25 10:00:00', $guardada->fecha_hora_cierre->toDateTimeString());
    }

    public function test_no_cierra_una_caja_que_ya_se_cerro_a_mano(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('listarVencidasDeRepartidores')->andReturn(new Collection([$this->caja()]));
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja(['estado' => Caja::ESTADO_CERRADA]));
        $repo->shouldReceive('guardar')->never();

        $this->assertSame(0, (new CajaService($repo))->cerrarCajasVencidas());
    }

    public function test_sin_cajas_vencidas_no_hace_nada(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('listarVencidasDeRepartidores')->andReturn(new Collection());
        $repo->shouldReceive('guardar')->never();

        $this->assertSame(0, (new CajaService($repo))->cerrarCajasVencidas());
    }

    // ---------- Cargar el conteo después ----------

    public function test_el_repartidor_no_carga_el_conteo_de_su_caja_cerrada_sola(): void
    {
        // Decisión 6: el repartidor abre otra caja y carga ahí lo pendiente.
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->cajaCerradaSola());
        $repo->shouldReceive('guardar')->never();

        $r = (new CajaService($repo))->completarConteoCierreAutomatico(7, 3, 1250.0, null, Caja::ROL_REPARTIDOR);

        $this->assertTrue($r['error']);
    }

    public function test_el_admin_carga_el_conteo_y_se_calcula_la_diferencia(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->cajaCerradaSola());
        $repo->shouldReceive('rolDeUsuario')->with(3)->andReturn(Caja::ROL_REPARTIDOR);
        $repo->shouldReceive('guardar')->once()->andReturnUsing(fn ($c) => $c);

        $r = (new CajaService($repo))->completarConteoCierreAutomatico(7, 1, 1250.0, null, Caja::ROL_ADMIN);

        $this->assertArrayNotHasKey('error', $r);
        $this->assertEquals(1250, $r['monto_final']);
        $this->assertEquals(-50, $r['diferencia']);        // 1250 - 1300
        $this->assertEquals(1, $r['id_usuario_cierre']);
    }

    public function test_el_admin_carga_el_conteo_de_la_caja_de_un_repartidor(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->cajaCerradaSola());
        $repo->shouldReceive('rolDeUsuario')->with(3)->andReturn(Caja::ROL_REPARTIDOR);
        $repo->shouldReceive('guardar')->once()->andReturnUsing(fn ($c) => $c);

        $r = (new CajaService($repo))->completarConteoCierreAutomatico(7, 1, 1300.0, null, Caja::ROL_ADMIN);

        $this->assertArrayNotHasKey('error', $r);
        $this->assertEquals(0, $r['diferencia']);
        $this->assertEquals(3, $r['id_usuario']);          // la caja sigue siendo del repartidor
        $this->assertEquals(1, $r['id_usuario_cierre']);
    }

    public function test_no_permite_cargar_conteo_en_una_caja_cerrada_a_mano(): void
    {
        $normal = $this->caja([
            'estado' => Caja::ESTADO_CERRADA,
            'monto_final' => 1000,
            'monto_esperado' => 1000,
            'diferencia' => 0,
        ]);

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($normal);
        $repo->shouldReceive('guardar')->never();

        $r = (new CajaService($repo))->completarConteoCierreAutomatico(7, 3, 900.0, null, Caja::ROL_REPARTIDOR);

        $this->assertTrue($r['error']);
    }

    public function test_no_permite_cargar_el_conteo_dos_veces(): void
    {
        $contada = $this->cajaCerradaSola(['monto_final' => 1250, 'diferencia' => -50]);

        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($contada);
        $repo->shouldReceive('guardar')->never();

        $r = (new CajaService($repo))->completarConteoCierreAutomatico(7, 3, 1300.0, null, Caja::ROL_REPARTIDOR);

        $this->assertTrue($r['error']);
    }

    public function test_otro_repartidor_no_puede_cargar_el_conteo(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->cajaCerradaSola());
        $repo->shouldReceive('rolDeUsuario')->never();
        $repo->shouldReceive('guardar')->never();

        $r = (new CajaService($repo))->completarConteoCierreAutomatico(7, 4, 1300.0, null, Caja::ROL_REPARTIDOR);

        $this->assertTrue($r['error']);
    }
}
