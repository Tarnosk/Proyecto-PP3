<?php
declare(strict_types=1);

namespace App\Modules\Compras\Repositories;

use App\Modules\Compras\Models\PagoProveedor;
use Illuminate\Database\Eloquent\Builder;

class PagoProveedorRepository
{
    public function crear(array $datos): PagoProveedor
    {
        return PagoProveedor::create($datos);
    }

    /**
     * Pagos de una compra, del más antiguo al más reciente.
     *
     * Devuelve modelos, no arrays, porque el service los formatea.
     */
    public function listarPorCompra(int $idCompra)
    {
        return $this->base($idCompra)
            ->orderBy('fecha_pago', 'asc')
            ->orderBy('id_pago', 'asc')
            ->get();
    }

    /** Un renglón por método de pago con el importe acumulado de cada uno (C08). */
    public function totalesPorMetodo(int $idCompra): array
    {
        return $this->base($idCompra)
            ->selectRaw('metodo_pago, count(*) as cantidad_pagos, sum(importe) as total')
            ->groupBy('metodo_pago')
            ->orderBy('metodo_pago', 'asc')
            ->get()
            ->map(fn ($r) => [
                'metodo_pago' => $r->metodo_pago,
                'cantidad_pagos' => (int) $r->cantidad_pagos,
                'total' => round((float) $r->total, 2),
            ])
            ->toArray();
    }

    public function totalPagado(int $idCompra): float
    {
        return round((float) $this->base($idCompra)->sum('importe'), 2);
    }

    public function contar(int $idCompra): int
    {
        return $this->base($idCompra)->count();
    }

    public function existeEnCompra(int $idPago, int $idCompra): bool
    {
        return $this->base($idCompra)->where('id_pago', $idPago)->exists();
    }

    private function base(int $idCompra): Builder
    {
        return PagoProveedor::where('id_compra', $idCompra);
    }
}