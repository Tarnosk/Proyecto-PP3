<?php
declare(strict_types=1);

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\Lote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consultas de vencimientos (S11) sobre la tabla LOTE del módulo Stock.
 *
 * Convenciones de Stock que se respetan (no las de producto_lotes):
 *  - vencido = fecha_vencimiento < hoy; el estado es DERIVADO, no una columna;
 *  - se excluyen los lotes consumidos con cantidad_actual <= 0 y los de productos inactivos;
 *  - "próximo a vencer" reutiliza Lote::porVencer() (vigente, hoy..hoy+N inclusive).
 *  - LOTE no tiene id_unidad: la unidad del ingreso se toma del movimiento.
 */
class LoteVencimientoRepository
{
    private function base(): Builder
    {
        return Lote::query()
            ->with(['producto', 'movimiento'])
            ->where('cantidad_actual', '>', 0)
            ->whereHas('producto', fn (Builder $q) => $q->where('estado', 'activo'));
    }

    private function filtrarProducto(Builder $query, ?string $termino): void
    {
        if ($termino === null || trim($termino) === '') {
            return;
        }
        $termino = trim($termino);
        $query->whereHas('producto', function (Builder $q) use ($termino) {
            $q->where(function (Builder $qq) use ($termino) {
                $qq->where('nombre', 'like', "%{$termino}%")
                   ->orWhere('descripcion', 'like', "%{$termino}%")
                   ->orWhere('codigo', 'like', "%{$termino}%");
            });
        });
    }

    /**
     * S11 - Criterio "Debe permitir ordenar por fecha de vencimiento".
     *
     * $columna se traduce a una columna real de una lista blanca: el parámetro
     * viene de la query string y no puede interpolarse en el ORDER BY.
     * 'dias_restantes' no existe como columna (fecha_vencimiento es fija), se
     * ordena por la fecha y se recalcula el signo en la vista.
     */
    private const COLUMNAS_ORDEN = [
        'fecha_vencimiento' => 'fecha_vencimiento',
        'cantidad' => 'cantidad_actual',
        'producto' => 'id_producto',
        'dias_restantes' => 'fecha_vencimiento',
    ];

    public function obtenerProximosAVencer(
        int $dias = 30,
        int $pagina = 1,
        int $porPagina = 15,
        array $filtros = [],
        string $columna = 'fecha_vencimiento',
        string $direccion = 'asc'
    ): array {
        $columna = self::COLUMNAS_ORDEN[$columna] ?? 'fecha_vencimiento';
        $direccion = $direccion === 'desc' ? 'desc' : 'asc';

        $query = $this->base()->porVencer($dias);
        $this->filtrarProducto($query, $filtros['producto'] ?? null);

        // El desempate por id_lote mantiene la paginación estable: sin él, dos
        // lotes con la misma fecha pueden repetirse o perderse entre páginas.
        $p = $query->orderBy($columna, $direccion)
            ->orderBy('id_lote', 'asc')
            ->paginate($porPagina, ['*'], 'page', $pagina);

        return [
            'data' => $p->items(),
            'orden' => ['columna' => $columna, 'direccion' => $direccion],
            'paginacion' => [
                'total' => $p->total(),
                'por_pagina' => $p->perPage(),
                'pagina_actual' => $p->currentPage(),
                'total_paginas' => $p->lastPage(),
                'tiene_siguiente' => $p->hasMorePages(),
            ],
        ];
    }

    /**
     * Lotes vigentes cuyo vencimiento cae entre hoy+$desde y hoy+$hasta (inclusive).
     * $hasta = null => sin tope superior. Rangos exclusivos entre sí si se encadenan
     * (0..7, 8..30, 31..null), a diferencia de producto_lotes que solapaba el día 7.
     */
    public function obtenerPorRangoDias(int $desde, ?int $hasta): array
    {
        $query = $this->base()
            ->where('fecha_vencimiento', '>=', Carbon::today()->addDays($desde)->toDateString());

        if ($hasta !== null) {
            $query->where('fecha_vencimiento', '<=', Carbon::today()->addDays($hasta)->toDateString());
        }

        return $query->orderBy('fecha_vencimiento', 'asc')->get()->all();
    }

    /** Cantidad total en lotes vigentes que vencen dentro de $dias para un producto. */
    public function cantidadEnRiesgoPorProducto(int $idProducto, int $dias): int
    {
        return (int) $this->base()
            ->porVencer($dias)
            ->where('id_producto', $idProducto)
            ->sum('cantidad_actual');
    }

    public function obtenerResumenAlertas(int $critico, int $proximo): array
    {
        $hoy = Carbon::today()->toDateString();
        $finCritico = Carbon::today()->addDays($critico)->toDateString();
        $iniProximo = Carbon::today()->addDays($critico + 1)->toDateString();
        $finProximo = Carbon::today()->addDays($proximo)->toDateString();

        $vigentesEntre = fn (string $d, string $h) => $this->base()
            ->whereBetween('fecha_vencimiento', [$d, $h]);

        return [
            'total_vencidos' => $this->base()->where('fecha_vencimiento', '<', $hoy)->count(),
            'criticos_7dias' => $vigentesEntre($hoy, $finCritico)->count(),
            // Exclusivo del tramo crítico para que los gráficos no cuenten dos veces el mismo lote.
            'proximos_30dias' => $vigentesEntre($iniProximo, $finProximo)->count(),
            'total_items_en_riesgo' => (int) $vigentesEntre($hoy, $finProximo)->sum('cantidad_actual'),
        ];
    }
}
