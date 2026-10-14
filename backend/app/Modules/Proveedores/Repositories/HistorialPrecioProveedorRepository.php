<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Repositories;

use App\Modules\Proveedores\Models\HistorialPrecioProveedor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HistorialPrecioProveedorRepository
{
    /**
     * Construye la consulta base para el historial de precios de un proveedor.
     */
    public function queryPorProveedor(
        int $idProveedor,
        ?int $idProducto = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        string $orden = 'desc'
    ): Builder {
        $query = HistorialPrecioProveedor::with([
            'producto.categoria',
            'producto.marca',
            'unidad',
            'compra',
            'usuario',
            'proveedor',
        ])->where('id_proveedor', $idProveedor);

        if ($idProducto !== null && $idProducto > 0) {
            $query->where('id_producto', $idProducto);
        }

        if (!empty($fechaDesde)) {
            $query->where('fecha_actualizacion', '>=', Carbon::parse($fechaDesde)->startOfDay());
        }

        if (!empty($fechaHasta)) {
            $query->where('fecha_actualizacion', '<=', Carbon::parse($fechaHasta)->endOfDay());
        }

        $direccion = strtolower($orden) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy('fecha_actualizacion', $direccion)
            ->orderBy('id_historial_prov', $direccion);
    }

    /**
     * Obtiene todos los registros que coinciden con los filtros.
     */
    public function obtenerPorProveedor(
        int $idProveedor,
        ?int $idProducto = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        string $orden = 'desc'
    ): Collection {
        return $this->queryPorProveedor($idProveedor, $idProducto, $fechaDesde, $fechaHasta, $orden)->get();
    }

    /**
     * Obtiene el historial completo en orden cronológico ascendente (para cálculo de variaciones secuenciales).
     */
    public function obtenerTodosAscendente(int $idProveedor, ?int $idProducto = null): Collection
    {
        $query = HistorialPrecioProveedor::with([
            'producto.categoria',
            'producto.marca',
            'unidad',
            'compra',
            'usuario',
        ])->where('id_proveedor', $idProveedor);

        if ($idProducto !== null && $idProducto > 0) {
            $query->where('id_producto', $idProducto);
        }

        return $query->orderBy('fecha_actualizacion', 'asc')
            ->orderBy('id_historial_prov', 'asc')
            ->get();
    }
}
