<?php
declare(strict_types=1);

namespace App\Modules\Stock\Repositories;

use App\Modules\Stock\Models\DetalleConteo;
use App\Modules\Stock\Models\SesionConteo;
use Illuminate\Database\Eloquent\Collection;

class ConteoFisicoRepository
{
    public function listar(?string $estado = null, ?string $fechaDesde = null, ?string $fechaHasta = null): Collection
    {
        $query = SesionConteo::with('usuario')->orderByDesc('fecha_conteo')->orderByDesc('id_sesion');

        if ($estado !== null && $estado !== '') {
            $query->where('estado', $estado);
        }

        if ($fechaDesde !== null && $fechaDesde !== '') {
            $query->whereDate('fecha_conteo', '>=', $fechaDesde);
        }

        if ($fechaHasta !== null && $fechaHasta !== '') {
            $query->whereDate('fecha_conteo', '<=', $fechaHasta);
        }

        return $query->get();
    }

    public function obtenerSesion(int $idSesion): ?SesionConteo
    {
        return SesionConteo::with(['usuario', 'detalles.producto'])->find($idSesion);
    }

    public function resumenPorSesion(int $idSesion): array
    {
        $detalles = DetalleConteo::where('id_sesion', $idSesion)->get();

        return [
            'total_contados' => $detalles->count(),
            'coincidentes' => $detalles->filter(fn ($detalle) => (int) $detalle->diferencia === 0)->count(),
            'faltantes' => $detalles->filter(fn ($detalle) => (int) $detalle->diferencia < 0)->count(),
            'sobrantes' => $detalles->filter(fn ($detalle) => (int) $detalle->diferencia > 0)->count(),
            'faltantes_unidades' => (int) $detalles->filter(fn ($detalle) => (int) $detalle->diferencia < 0)->sum(fn ($detalle) => abs((int) $detalle->diferencia)),
            'sobrantes_unidades' => (int) $detalles->filter(fn ($detalle) => (int) $detalle->diferencia > 0)->sum(fn ($detalle) => (int) $detalle->diferencia),
            'inconsistencias' => $detalles->filter(fn ($detalle) => (int) $detalle->diferencia !== 0)->count(),
        ];
    }
}
