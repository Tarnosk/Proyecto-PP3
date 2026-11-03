<?php

namespace Tests\Unit\Caja;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Repositories\CajaRepository;
use App\Modules\Caja\Services\CajaService;
use Mockery;
use Tests\TestCase;

/**
 * El admin (Diego) puede cerrar la caja de un repartidor, pero no la de otro
 * admin. Un repartidor no puede cerrar la caja de otro. El repositorio se
 * simula (Mockery).
 */
class CajaCierreAdminTest extends TestCase
{
    private function caja(int $idUsuario): Caja
    {
        $caja = new Caja([
            'id_usuario' => $idUsuario,
            'monto_inicial' => 1000,
            'estado' => Caja::ESTADO_ABIERTA,
        ]);
        $caja->id_caja = 7;

        return $caja;
    }

    public function test_admin_puede_cerrar_la_caja_de_un_repartidor(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja(3));
        $repo->shouldReceive('rolDeUsuario')->with(3)->andReturn(Caja::ROL_REPARTIDOR);
        $repo->shouldReceive('calcularSaldoMovimientos')->with(7)->andReturn(0.0);
        $repo->shouldReceive('guardar')->once()->andReturnUsing(fn ($c) => $c);

        $r = (new CajaService($repo))->cerrarCaja(7, 1, 950.0, null, Caja::ROL_ADMIN);

        $this->assertArrayNotHasKey('error', $r);
        $this->assertEquals(3, $r['id_usuario']);          // la caja sigue siendo del repartidor
        $this->assertEquals(1, $r['id_usuario_cierre']);   // la cerró el admin
        $this->assertEquals(-50, $r['diferencia']);
    }

    public function test_admin_no_puede_cerrar_la_caja_de_otro_admin(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja(2));
        $repo->shouldReceive('rolDeUsuario')->with(2)->andReturn(Caja::ROL_ADMIN);
        $repo->shouldReceive('guardar')->never();

        $r = (new CajaService($repo))->cerrarCaja(7, 1, 1000.0, null, Caja::ROL_ADMIN);

        $this->assertTrue($r['error']);
    }

    public function test_repartidor_no_puede_cerrar_la_caja_de_otro_repartidor(): void
    {
        $repo = Mockery::mock(CajaRepository::class);
        $repo->shouldReceive('buscarPorId')->with(7)->andReturn($this->caja(3));
        $repo->shouldReceive('rolDeUsuario')->never();
        $repo->shouldReceive('guardar')->never();

        $r = (new CajaService($repo))->cerrarCaja(7, 4, 1000.0, null, Caja::ROL_REPARTIDOR);

        $this->assertTrue($r['error']);
    }
}
