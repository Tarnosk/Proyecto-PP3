<?php

namespace Tests\Unit\Caja;

use App\Modules\Caja\Models\Caja;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Aviso previo al vencimiento: faltan 24 h o menos y la caja sigue abierta.
 * Aplica a repartidor (72 h) y a admin (7 días / fin de mes).
 */
class CajaPorVencerTest extends TestCase
{
    private function caja(string $estado, ?string $vencimiento): Caja
    {
        return new Caja([
            'estado' => $estado,
            'fecha_hora_vencimiento' => $vencimiento !== null ? Carbon::parse($vencimiento) : null,
        ]);
    }

    public function test_abierta_con_mas_de_24_horas_no_esta_por_vencer(): void
    {
        $ahora = Carbon::parse('2026-11-04 12:00:00');
        $caja = $this->caja(Caja::ESTADO_ABIERTA, '2026-11-05 12:01:00');

        $this->assertFalse($caja->estaPorVencer($ahora));
        $this->assertFalse($caja->estaVencida($ahora));
    }

    public function test_abierta_justo_a_24_horas_esta_por_vencer(): void
    {
        $ahora = Carbon::parse('2026-11-04 12:00:00');
        $caja = $this->caja(Caja::ESTADO_ABIERTA, '2026-11-05 12:00:00');

        $this->assertTrue($caja->estaPorVencer($ahora));
        $this->assertSame(24.0, $caja->horasRestantes($ahora));
    }

    public function test_abierta_con_pocas_horas_esta_por_vencer(): void
    {
        $ahora = Carbon::parse('2026-11-04 12:00:00');
        $caja = $this->caja(Caja::ESTADO_ABIERTA, '2026-11-04 15:30:00');

        $this->assertTrue($caja->estaPorVencer($ahora));
        $this->assertSame(3.5, $caja->horasRestantes($ahora));
    }

    public function test_abierta_ya_vencida_no_esta_por_vencer_pero_si_vencida(): void
    {
        $ahora = Carbon::parse('2026-11-04 12:00:00');
        $caja = $this->caja(Caja::ESTADO_ABIERTA, '2026-11-04 11:59:00');

        $this->assertTrue($caja->estaVencida($ahora));
        $this->assertFalse($caja->estaPorVencer($ahora));
        $this->assertNull($caja->horasRestantes($ahora));
    }

    public function test_caja_cerrada_nunca_esta_por_vencer_ni_vencida(): void
    {
        $ahora = Carbon::parse('2026-11-04 12:00:00');

        $porVencer = $this->caja(Caja::ESTADO_CERRADA, '2026-11-04 15:00:00');
        $vencida = $this->caja(Caja::ESTADO_CERRADA, '2026-11-03 15:00:00');

        $this->assertFalse($porVencer->estaPorVencer($ahora));
        $this->assertNull($porVencer->horasRestantes($ahora));
        $this->assertFalse($vencida->estaVencida($ahora));
    }

    public function test_sin_fecha_de_vencimiento_no_avisa(): void
    {
        $ahora = Carbon::parse('2026-11-04 12:00:00');
        $caja = $this->caja(Caja::ESTADO_ABIERTA, null);

        $this->assertFalse($caja->estaPorVencer($ahora));
        $this->assertFalse($caja->estaVencida($ahora));
        $this->assertNull($caja->horasRestantes($ahora));
    }

    public function test_caja_de_admin_a_siete_dias_no_avisa_hasta_las_ultimas_24_horas(): void
    {
        // Admin abre el 1/11 a las 10:00: vence el 8/11 a las 10:00.
        $caja = $this->caja(Caja::ESTADO_ABIERTA, '2026-11-08 10:00:00');

        $this->assertFalse($caja->estaPorVencer(Carbon::parse('2026-11-05 10:00:00')));
        $this->assertTrue($caja->estaPorVencer(Carbon::parse('2026-11-07 10:00:00')));
    }

    public function test_los_campos_derivados_viajan_en_to_array(): void
    {
        Carbon::setTestNow('2026-11-04 12:00:00');

        try {
            $datos = $this->caja(Caja::ESTADO_ABIERTA, '2026-11-04 20:00:00')->toArray();

            $this->assertTrue($datos['por_vencer']);
            $this->assertFalse($datos['vencida']);
            $this->assertSame(8.0, $datos['horas_restantes']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
