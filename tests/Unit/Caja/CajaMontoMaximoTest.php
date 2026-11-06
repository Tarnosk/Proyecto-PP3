<?php

namespace Tests\Unit\Caja;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Models\CajaMovimiento;
use App\Modules\Caja\Repositories\CajaRepository;
use App\Modules\Caja\Services\CajaService;
use Mockery;
use Tests\TestCase;

/**
 * Tope de negocio para los montos (las columnas son decimal(12,2)).
 * Un monto fuera de rango se rechaza con un mensaje claro en vez de llegar
 * a MySQL y devolver un error 500.
 */
class CajaMontoMaximoTest extends TestCase
{
    private const PASADO_DE_RANGO = 100000000.00; // un centavo por encima del tope

    private $repositorio;
    private CajaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositorio = Mockery::mock(CajaRepository::class);
        $this->service = new CajaService($this->repositorio);
    }

    public function test_el_tope_entra_en_la_columna_de_la_base(): void
    {
        // decimal(12,2) admite hasta 9.999.999.999,99
        $this->assertLessThan(9999999999.99, Caja::MONTO_MAXIMO);
        $this->assertSame('El monto no puede superar $99.999.999,99.', Caja::MENSAJE_MONTO_MAXIMO);
    }

    public function test_abrir_caja_rechaza_monto_inicial_pasado_de_rango(): void
    {
        $this->repositorio->shouldReceive('buscarCualquierAbiertaDeUsuario')->andReturn(null);
        $this->repositorio->shouldNotReceive('crear');

        $resultado = $this->service->abrirCaja(3, Caja::ROL_REPARTIDOR, self::PASADO_DE_RANGO);

        $this->assertTrue($resultado['error']);
        $this->assertSame(Caja::MENSAJE_MONTO_MAXIMO, $resultado['mensaje']);
    }

    public function test_abrir_caja_acepta_exactamente_el_tope(): void
    {
        $this->repositorio->shouldReceive('buscarCualquierAbiertaDeUsuario')->andReturn(null);
        $this->repositorio->shouldReceive('crear')->once()->andReturn(new Caja(['monto_inicial' => Caja::MONTO_MAXIMO]));

        $resultado = $this->service->abrirCaja(3, Caja::ROL_REPARTIDOR, Caja::MONTO_MAXIMO);

        $this->assertArrayNotHasKey('error', $resultado);
    }

    public function test_cerrar_caja_rechaza_monto_final_pasado_de_rango(): void
    {
        $this->repositorio->shouldReceive('buscarPorId')->andReturn(new Caja(['id_usuario' => 3, 'estado' => Caja::ESTADO_ABIERTA]));
        $this->repositorio->shouldNotReceive('guardar');

        // El caso real: 15.331.800.000 de monto final reventaba la columna.
        $resultado = $this->service->cerrarCaja(1, 3, 15331800000.00, null, Caja::ROL_REPARTIDOR);

        $this->assertTrue($resultado['error']);
        $this->assertSame(Caja::MENSAJE_MONTO_MAXIMO, $resultado['mensaje']);
    }

    public function test_conteo_de_cierre_automatico_rechaza_monto_pasado_de_rango(): void
    {
        $this->repositorio->shouldReceive('buscarPorId')->andReturn(new Caja([
            'id_usuario' => 3,
            'estado' => Caja::ESTADO_CERRADA,
            'cierre_automatico' => true,
        ]));
        $this->repositorio->shouldReceive('rolDeUsuario')->andReturn(Caja::ROL_REPARTIDOR);
        $this->repositorio->shouldNotReceive('guardar');

        $resultado = $this->service->completarConteoCierreAutomatico(1, 1, self::PASADO_DE_RANGO, null, Caja::ROL_ADMIN);

        $this->assertTrue($resultado['error']);
        $this->assertSame(Caja::MENSAJE_MONTO_MAXIMO, $resultado['mensaje']);
    }

    public function test_registrar_movimiento_rechaza_monto_pasado_de_rango(): void
    {
        $this->repositorio->shouldReceive('buscarPorId')->andReturn(new Caja(['id_usuario' => 1, 'estado' => Caja::ESTADO_ABIERTA]));
        $this->repositorio->shouldNotReceive('crearMovimiento');

        $tipo = CajaMovimiento::tiposPermitidosPara(Caja::ROL_ADMIN)[0];

        $resultado = $this->service->registrarMovimiento(1, 1, Caja::ROL_ADMIN, $tipo, 'Prueba', self::PASADO_DE_RANGO);

        $this->assertTrue($resultado['error']);
        $this->assertSame(Caja::MENSAJE_MONTO_MAXIMO, $resultado['mensaje']);
    }

    public function test_reversion_de_cobro_rechaza_monto_pasado_de_rango(): void
    {
        $this->repositorio->shouldReceive('buscarCualquierAbiertaDeUsuario')->andReturn(new Caja(['id_usuario' => 1, 'estado' => Caja::ESTADO_ABIERTA]));
        $this->repositorio->shouldNotReceive('crearMovimiento');

        $resultado = $this->service->registrarReversionDeCobro(1, Caja::ROL_ADMIN, self::PASADO_DE_RANGO, 'Anulación', 10);

        $this->assertTrue($resultado['error']);
        $this->assertSame(Caja::MENSAJE_MONTO_MAXIMO, $resultado['mensaje']);
    }
}
