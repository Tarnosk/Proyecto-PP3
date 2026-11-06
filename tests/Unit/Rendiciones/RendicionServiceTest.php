<?php

namespace Tests\Unit\Rendiciones;

use App\Modules\Entregas\Integraciones\VentasApiClient;
use App\Modules\Rendiciones\Models\Rendicion;
use App\Modules\Rendiciones\Models\RendicionCobro;
use App\Modules\Rendiciones\Repositories\RendicionRepository;
use App\Modules\Rendiciones\Services\RendicionService;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Pruebas unitarias de RendicionService (Grupo 2).
 * Valida la lógica de negocio, cálculos de totales, estados y resiliencia
 * aislando dependencias externas.
 */
class RendicionServiceTest extends TestCase
{
    private function rendicionFake(array $attrs): Rendicion
    {
        $r = new Rendicion($attrs);
        if (isset($attrs['id_rendicion'])) {
            $r->id_rendicion = $attrs['id_rendicion'];
        }
        return $r;
    }

    public function test_registrar_rendicion_calcula_total_y_guarda_en_estado_pendiente(): void
    {
        $repoMock = $this->createMock(RendicionRepository::class);
        $ventasMock = $this->createMock(VentasApiClient::class);

        $repoMock->method('repartidorEstaActivo')->with(2)->willReturn(true);
        $repoMock->method('entregaExiste')->with(1)->willReturn(true);

        $datosCobros = [
            [
                'id_cliente' => 1,
                'id_factura' => 10,
                'monto' => 15000.50,
                'medio_pago' => 'efectivo',
            ],
            [
                'id_cliente' => 2,
                'id_factura' => 11,
                'monto' => 25000.00,
                'medio_pago' => 'transferencia',
            ],
        ];

        $repoMock->expects($this->once())
            ->method('crear')
            ->with($this->callback(function ($datos) {
                return $datos['id_repartidor'] === 2
                    && $datos['id_entrega'] === 1;
            }))
            ->willReturn($this->rendicionFake([
                'id_rendicion' => 1,
                'id_repartidor' => 2,
                'id_entrega' => 1,
                'total_rendido' => 0.0,
                'estado' => Rendicion::ESTADO_PENDIENTE,
            ]));

        $repoMock->expects($this->exactly(2))
            ->method('agregarCobro')
            ->willReturnCallback(function ($idRendicion, $cobro) {
                return new RendicionCobro(array_merge(['id_rendicion' => $idRendicion], $cobro));
            });

        $service = new RendicionService($repoMock, $ventasMock);
        $resultado = $service->registrarRendicion([
            'id_repartidor' => 2,
            'id_entrega' => 1,
            'cobros' => $datosCobros,
            'observaciones' => 'Rendición de prueba',
        ]);

        $this->assertEquals(1, $resultado['id_rendicion']);
        $this->assertEquals(40000.50, $resultado['total_rendido']);
        $this->assertEquals(Rendicion::ESTADO_PENDIENTE, $resultado['estado']);
        $this->assertEquals(2, $resultado['cantidad_cobros_asociados']);
    }

    public function test_revisar_rendicion_lanza_excepcion_si_no_existe(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No se encontró la rendición');

        $repoMock = $this->createMock(RendicionRepository::class);
        $repoMock->method('buscarPorId')->with(999)->willReturn(null);

        $service = new RendicionService($repoMock);
        $service->revisarRendicion(999, ['accion' => 'aprobar', 'id_usuario_validador' => 1]);
    }

    public function test_revisar_rendicion_lanza_excepcion_si_ya_fue_validada(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Solo se puede aprobar una rendición en estado pendiente');

        $rYaAprobada = $this->rendicionFake([
            'id_rendicion' => 5,
            'id_repartidor' => 2,
            'estado' => Rendicion::ESTADO_APROBADA,
        ]);

        $repoMock = $this->createMock(RendicionRepository::class);
        $repoMock->method('buscarPorId')->with(5)->willReturn($rYaAprobada);

        $service = new RendicionService($repoMock);
        $service->revisarRendicion(5, ['accion' => 'aprobar', 'id_usuario_validador' => 1]);
    }

    public function test_rechazar_rendicion_sin_motivo_lanza_excepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('motivo');

        $rPendiente = $this->rendicionFake([
            'id_rendicion' => 3,
            'id_repartidor' => 2,
            'estado' => Rendicion::ESTADO_PENDIENTE,
        ]);

        $repoMock = $this->createMock(RendicionRepository::class);
        $repoMock->method('buscarPorId')->with(3)->willReturn($rPendiente);

        $service = new RendicionService($repoMock);
        $service->revisarRendicion(3, ['accion' => 'rechazar', 'id_usuario_validador' => 1, 'motivo_rechazo' => '']);
    }

    public function test_resiliencia_si_falla_api_externa_ventas_g4_la_aprobacion_no_se_interrumpe(): void
    {
        $rPendiente = $this->rendicionFake([
            'id_rendicion' => 4,
            'id_repartidor' => 2,
            'total_rendido' => 10000.00,
            'estado' => Rendicion::ESTADO_PENDIENTE,
        ]);

        $cobro = new RendicionCobro([
            'id_rendicion_cobro' => 10,
            'id_rendicion' => 4,
            'id_cliente' => 1,
            'id_factura' => 888,
            'monto' => 10000.00,
            'medio_pago' => 'transferencia',
        ]);
        $rPendiente->setRelation('cobros', collect([$cobro]));

        $repoMock = $this->createMock(RendicionRepository::class);
        $repoMock->method('buscarPorId')->with(4)->willReturn($rPendiente);

        $ventasMock = $this->createMock(VentasApiClient::class);
        // Simulamos que la API externa de Ventas (G4) arroja una excepción o error 500
        $ventasMock->expects($this->once())
            ->method('actualizarEstadoVenta')
            ->with(888, 'pagada')
            ->willThrowException(new \RuntimeException('Connection timed out to Ventas G4'));

        $service = new RendicionService($repoMock, $ventasMock);

        // La aprobación debe completarse con éxito a pesar de la caída de la API externa
        $resultado = $service->revisarRendicion(4, [
            'accion' => 'aprobar',
            'id_usuario_validador' => 1,
        ]);

        $this->assertEquals(Rendicion::ESTADO_APROBADA, $resultado['estado_actual']);
    }
}
