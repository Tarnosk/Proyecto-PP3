<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Services;

use App\Modules\PedidosDeCompra\Repositories\SugerenciaCompraRepository;
use App\Modules\Stock\Repositories\LoteVencimientoRepository;

class DashboardReposicionService
{
    public function __construct(
        private SugerenciaCompraRepository $sugerencias,
        private LoteVencimientoRepository $lotes
    ) {
    }

    private function resumenVencimientos(): array
    {
        return $this->lotes->obtenerResumenAlertas(
            (int) config('reposicion.critico_dias', 7),
            (int) config('reposicion.proximo_dias', 30)
        );
    }

    public function obtenerResumenGeneral(): array
    {
        return [
            'exito' => true,
            'sugerencias' => $this->sugerencias->obtenerResumen(),
            'vencimientos' => $this->resumenVencimientos(),
        ];
    }

    public function obtenerDatosGraficoMotivos(): array
    {
        $etiquetas = [
            'bajo_stock' => 'Stock Bajo',
            'proximo_vencer' => 'Próximo a Vencer',
            'agotamiento_inmediato' => 'Agotamiento Inmediato',
        ];

        return [
            'exito' => true,
            'datos' => collect($this->sugerencias->obtenerEstadisticasPorMotivo())->map(fn ($f) => [
                'motivo' => $etiquetas[$f['motivo_generacion']] ?? $f['motivo_generacion'],
                'total' => (int) $f['total'],
                'cantidad' => (int) $f['cantidad'],
                'costo' => round((float) $f['costo'], 2),
            ])->all(),
        ];
    }

    public function obtenerDatosGraficoTendencia(int $dias = 30): array
    {
        return [
            'exito' => true,
            'datos' => collect($this->sugerencias->obtenerTendenciaUltimosDias($dias))->map(fn ($f) => [
                'fecha' => $f['fecha'],
                'total' => (int) $f['total'],
                'costo' => round((float) $f['costo'], 2),
            ])->all(),
        ];
    }

    public function obtenerDatosGraficoCriticidad(): array
    {
        $r = $this->resumenVencimientos();

        return [
            'exito' => true,
            'datos' => [
                ['categoria' => 'Vencidos', 'total' => $r['total_vencidos']],
                ['categoria' => 'Críticos (7 días)', 'total' => $r['criticos_7dias']],
                ['categoria' => 'Próximos (30 días)', 'total' => $r['proximos_30dias']],
            ],
        ];
    }
}
