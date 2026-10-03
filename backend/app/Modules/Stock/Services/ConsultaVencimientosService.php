<?php
declare(strict_types=1);

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Models\Lote;
use App\Modules\Stock\Repositories\LoteVencimientoRepository;

/**
 * S11 - Consulta de productos próximos a vencer.
 * Mantiene el contrato JSON del paquete original (exito/datos/paginacion/resumen)
 * porque lo consume public/js/vencimientos.js.
 */
class ConsultaVencimientosService
{
    public function __construct(private LoteVencimientoRepository $repository)
    {
    }

    private function critico(): int
    {
        return (int) config('reposicion.critico_dias', 7);
    }

    private function proximo(): int
    {
        return (int) config('reposicion.proximo_dias', 30);
    }

    public function obtenerProximosVencer(
        int $dias = 30,
        array $filtros = [],
        int $pagina = 1,
        string $columna = 'fecha_vencimiento',
        string $direccion = 'asc'
    ): array {
        $resultado = $this->repository->obtenerProximosAVencer($dias, $pagina, 15, $filtros, $columna, $direccion);

        return [
            'exito' => true,
            'datos' => collect($resultado['data'])->map(fn (Lote $l) => $this->formatearLote($l))->all(),
            // Se devuelve el orden aplicado para que la UI muestre la flecha correcta
            // sin tener que adivinarlo (y para que el fallback sea visible en la respuesta).
            'orden' => $resultado['orden'],
            'paginacion' => $resultado['paginacion'],
            'resumen' => $this->repository->obtenerResumenAlertas($this->critico(), $this->proximo()),
        ];
    }

    public function obtenerPorCriticidad(): array
    {
        $tramos = [
            'critico' => $this->repository->obtenerPorRangoDias(0, $this->critico()),
            'proximo' => $this->repository->obtenerPorRangoDias($this->critico() + 1, $this->proximo()),
            'seguro' => $this->repository->obtenerPorRangoDias($this->proximo() + 1, null),
        ];

        $respuesta = ['exito' => true];
        foreach ($tramos as $nombre => $lotes) {
            $respuesta[$nombre] = [
                'total' => count($lotes),
                'cantidad' => collect($lotes)->sum('cantidad_actual'),
                'productos' => collect($lotes)->map(fn (Lote $l) => $this->formatearLote($l))->all(),
            ];
        }

        return $respuesta;
    }

    public function obtenerAlertas(): array
    {
        $r = $this->repository->obtenerResumenAlertas($this->critico(), $this->proximo());

        return [
            'exito' => true,
            'alertas' => [
                ['tipo' => 'critica', 'titulo' => 'Productos Vencidos', 'cantidad' => $r['total_vencidos'],
                 'urgencia' => 'CRÍTICA', 'accion_recomendada' => 'Revisar y dar de baja los lotes vencidos'],
                ['tipo' => 'alta', 'titulo' => "Vencimiento en {$this->critico()} días", 'cantidad' => $r['criticos_7dias'],
                 'urgencia' => 'ALTA', 'accion_recomendada' => 'Priorizar venta o descuento'],
                ['tipo' => 'media', 'titulo' => "Vencimiento en {$this->proximo()} días", 'cantidad' => $r['proximos_30dias'],
                 'urgencia' => 'MEDIA', 'accion_recomendada' => 'Monitorear'],
            ],
        ];
    }

    public function generarReporte(): array
    {
        $lotes = $this->repository->obtenerProximosAVencer(999, 1, 10000)['data'];

        $datos = collect($lotes)->map(function (Lote $l) {
            $dias = (int) $l->dias_para_vencer;
            return [
                'Código Producto' => $l->producto?->codigo,
                'Nombre Producto' => $l->producto?->nombre ?? $l->producto?->descripcion,
                'Lote' => $l->nro_lote,
                'Cantidad' => $l->cantidad,
                'Fecha Vencimiento' => $l->fecha_vencimiento?->format('d/m/Y'),
                'Días Restantes' => $dias,
                'Estado' => $this->determinarEstadoVencimiento($dias),
                'Urgencia' => $this->obtenerNivelUrgencia($dias),
            ];
        })->all();

        return [
            'exito' => true,
            'datos' => $datos,
            'total_registros' => count($datos),
            'fecha_generacion' => now()->format('Y-m-d H:i:s'),
        ];
    }

    private function formatearLote(Lote $l): array
    {
        $dias = (int) $l->dias_para_vencer;

        return [
            'id' => $l->id_lote,
            'producto' => [
                'id' => $l->id_producto,
                'nombre' => $l->producto?->nombre ?? $l->producto?->descripcion,
                'codigo' => $l->producto?->codigo,
'unidad_medida' => $l->movimiento?->unidad?->nombre_unidad ?? 'und',
              ],
              'lote' => [
                  'numero' => $l->nro_lote,
                  'cantidad' => $l->cantidad_actual,
              ],
            'vencimiento' => [
                'fecha' => $l->fecha_vencimiento?->format('Y-m-d'),
                'dias_restantes' => $dias,
                'estado' => $this->determinarEstadoVencimiento($dias),
                'urgencia' => $this->obtenerNivelUrgencia($dias),
            ],
            // LOTE no tiene ubicación de almacén (existía solo en producto_lotes).
            'ubicacion' => null,
        ];
    }

    private function determinarEstadoVencimiento(int $dias): string
    {
        if ($dias < 0) return 'vencido';
        if ($dias <= $this->critico()) return 'crítico';
        if ($dias <= $this->proximo()) return 'próximo';
        return 'seguro';
    }

    private function obtenerNivelUrgencia(int $dias): string
    {
        if ($dias < 0) return '🔴 CRÍTICA';
        if ($dias <= $this->critico()) return '🟠 ALTA';
        if ($dias <= $this->proximo()) return '🟡 MEDIA';
        return '🟢 BAJA';
    }
}
