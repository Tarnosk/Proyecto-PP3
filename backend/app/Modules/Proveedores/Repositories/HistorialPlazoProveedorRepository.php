<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Repositories;

use App\Modules\Proveedores\Models\HistorialPlazoProveedor;

class HistorialPlazoProveedorRepository
{
    public function registrar(array $datos): HistorialPlazoProveedor
    {
        return HistorialPlazoProveedor::create($datos);
    }

    /**
     * Cambios de plazo del proveedor, del más reciente al más antiguo.
     *
     * Devuelve modelos, no arrays, porque el service los formatea.
     */
    public function listarPorProveedor(int $idProveedor, int $limite = 0)
    {
        $query = HistorialPlazoProveedor::where('id_proveedor', $idProveedor)
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id_historial_plazo');

        if ($limite > 0) {
            $query->limit($limite);
        }

        return $query->get();
    }

    public function contar(int $idProveedor): int
    {
        return HistorialPlazoProveedor::where('id_proveedor', $idProveedor)->count();
    }
}