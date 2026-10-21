<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Repositories;

use App\Modules\PedidosDeCompra\Models\SugerenciaCompra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SugerenciaCompraRepository
{
    public function crear(array $datos): SugerenciaCompra
    {
        return SugerenciaCompra::create($datos);
    }

    /** Evita duplicar una sugerencia pendiente del mismo producto y motivo. */
    public function existePendiente(int $idProducto, string $motivo): bool
    {
        return SugerenciaCompra::pendientes()
            ->where('id_producto', $idProducto)
            ->where('motivo_generacion', $motivo)
            ->exists();
    }

    public function obtenerPorId(int $id): ?SugerenciaCompra
    {
        return SugerenciaCompra::with(['producto', 'proveedor'])->find($id);
    }

    /**
     * Relee la sugerencia con SELECT ... FOR UPDATE.
     *
     * Solo tiene efecto dentro de una transacción: bloquea la fila hasta que la
     * transacción confirme, de modo que dos peticiones simultáneas que quieren
     * actuar sobre la misma sugerencia quedan serializadas en vez de pasar ambas
     * la validación y generar dos órdenes.
     */
    public function obtenerPorIdBloqueado(int $id): ?SugerenciaCompra
    {
        return SugerenciaCompra::with(['producto', 'proveedor'])
            ->lockForUpdate()
            ->find($id);
    }

    /**
     * Pasa de pendiente a procesada/rechazada. Devuelve false si no existe o si
     * ya no estaba pendiente (evita reprocesar o "revivir" una rechazada).
     *
     * $motivoRechazo solo se escribe cuando viene informado, así una sugerencia
     * procesada nunca deja un motivo de rechazo suelta.
     */
    public function resolver(int $id, string $nuevoEstado, int $idUsuario, ?string $motivoRechazo = null): bool
    {
        $datos = [
            'estado' => $nuevoEstado,
            'fecha_resolucion' => now(),
            'id_usuario_resolucion' => $idUsuario,
        ];

        if ($motivoRechazo !== null && trim($motivoRechazo) !== '') {
            $datos['motivo_rechazo'] = trim($motivoRechazo);
        }

        return SugerenciaCompra::where('id_sugerencia', $id)
            ->where('estado', 'pendiente')
            ->update($datos) > 0;
    }

    public function procesarMultiples(array $ids, int $idUsuario): array
    {
        $procesadas = 0;
        $fallidas = [];

        foreach ($ids as $id) {
            if ($this->resolver((int) $id, 'procesada', $idUsuario)) {
                $procesadas++;
            } else {
                $fallidas[] = $id;
            }
        }

        return ['procesadas' => $procesadas, 'fallidas' => $fallidas];
    }

    public function obtenerConFiltros(array $filtros = [], int $pagina = 1, int $porPagina = 15): array
    {
        $query = SugerenciaCompra::with(['producto', 'proveedor']);

        if (!empty($filtros['motivo'])) {
            $query->where('motivo_generacion', $filtros['motivo']);
        }

        $query->where('estado', $filtros['estado'] ?? 'pendiente');

        return $this->paginar(
            $query->orderBy('fecha_reorden', 'asc')->orderBy('id_sugerencia', 'asc')
                  ->paginate($porPagina, ['*'], 'page', $pagina)
        );
    }

    public function obtenerEstadisticasPorMotivo(): array
    {
        return SugerenciaCompra::pendientes()
            ->selectRaw('motivo_generacion, count(*) as total, sum(cantidad_sugerida) as cantidad, sum(costo_total) as costo')
            ->groupBy('motivo_generacion')
            ->get()
            ->toArray();
    }

    public function obtenerTendenciaUltimosDias(int $dias = 30): array
    {
        return SugerenciaCompra::query()
            ->selectRaw('DATE(fecha_generacion) as fecha, count(*) as total, sum(costo_total) as costo')
            ->where('fecha_generacion', '>=', now()->subDays($dias)->startOfDay())
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get()
            ->toArray();
    }

    public function obtenerResumen(): array
    {
        return [
            'total_pendientes' => SugerenciaCompra::pendientes()->count(),
            'costo_total_sugerido' => (float) SugerenciaCompra::pendientes()->sum('costo_total'),
            'cantidad_total' => (int) SugerenciaCompra::pendientes()->sum('cantidad_sugerida'),
        ];
    }

    private function paginar(LengthAwarePaginator $p): array
    {
        return [
            'data' => $p->items(),
            'paginacion' => [
                'total' => $p->total(),
                'por_pagina' => $p->perPage(),
                'pagina_actual' => $p->currentPage(),
                'total_paginas' => $p->lastPage(),
                'tiene_siguiente' => $p->hasMorePages(),
            ],
        ];
    }
}
