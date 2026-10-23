<?php

namespace App\Services;

use App\Modules\Ventas\Contracts\ProductoGateway;

// Cumple el contrato ProductoGateway: Ventas lo usa sin saber que es un mock (ver config/ventas.php).
class MockProductoApi implements ProductoGateway
{
    /**
     * Datos de productos mock. Estructura pensada para cubrir:
     *  - POS (VentaController): id, codigo, nombre, precioMin, precioMay, stock
     *  - ABM (ProductoController): descripcion, categoria, marca, estado, fechas, usuarios
     *
     * Cuando el módulo de Productos (G3) esté listo, se reemplaza por una consulta real.
     */
    public function todos(): array
    {
        return [
            [
                'id'                => 1,
                'codigo'            => 'P001',
                'nombre'            => 'Coca-Cola 1.5L',
                'descripcion'       => 'Gaseosa Coca-Cola botella 1.5L',
                'id_categoria'      => 1,
                'nombre_categoria'  => 'Bebidas',
                'id_marca'          => 1,
                'nombre_marca'      => 'Coca-Cola',
                'precioMin'         => 2500,
                'precioMay'         => 2200,
                'precio_unitario'   => 2500.00,
                'stock'             => 48,
                'estado'            => 'activo',
                'fecha_alta'        => '2026-09-01',
                'fecha_modificacion'=> null,
                'usuario_carga'     => 'Estefanía Gianovich',
                'usuario_modificacion' => null,
            ],
            [
                'id'                => 2,
                'codigo'            => 'P002',
                'nombre'            => 'Pan lactal grande',
                'descripcion'       => 'Pan lactal grande 500g',
                'id_categoria'      => 2,
                'nombre_categoria'  => 'Panadería',
                'id_marca'          => 2,
                'nombre_marca'      => 'Bimbo',
                'precioMin'         => 1800,
                'precioMay'         => 1600,
                'precio_unitario'   => 1800.00,
                'stock'             => 30,
                'estado'            => 'activo',
                'fecha_alta'        => '2026-09-02',
                'fecha_modificacion'=> null,
                'usuario_carga'     => 'Estefanía Gianovich',
                'usuario_modificacion' => null,
            ],
            [
                'id'                => 3,
                'codigo'            => 'P003',
                'nombre'            => 'Yerba Mate 1kg',
                'descripcion'       => 'Yerba Mate Playadito 1kg',
                'id_categoria'      => 3,
                'nombre_categoria'  => 'Almacén',
                'id_marca'          => 3,
                'nombre_marca'      => 'Playadito',
                'precioMin'         => 4200,
                'precioMay'         => 3900,
                'precio_unitario'   => 4200.00,
                'stock'             => 25,
                'estado'            => 'activo',
                'fecha_alta'        => '2026-09-05',
                'fecha_modificacion'=> null,
                'usuario_carga'     => 'Nicolás Pérez',
                'usuario_modificacion' => null,
            ],
            [
                'id'                => 4,
                'codigo'            => 'P004',
                'nombre'            => 'Aceite girasol 900ml',
                'descripcion'       => 'Aceite de girasol 900ml',
                'id_categoria'      => 3,
                'nombre_categoria'  => 'Almacén',
                'id_marca'          => 4,
                'nombre_marca'      => 'Natura',
                'precioMin'         => 2100,
                'precioMay'         => 1950,
                'precio_unitario'   => 2100.00,
                'stock'             => 60,
                'estado'            => 'activo',
                'fecha_alta'        => '2026-09-10',
                'fecha_modificacion'=> null,
                'usuario_carga'     => 'Nicolás Pérez',
                'usuario_modificacion' => null,
            ],
            [
                'id'                => 5,
                'codigo'            => 'P005',
                'nombre'            => 'Arroz 1kg',
                'descripcion'       => 'Arroz largo fino 1kg',
                'id_categoria'      => 3,
                'nombre_categoria'  => 'Almacén',
                'id_marca'          => 5,
                'nombre_marca'      => 'Gallo',
                'precioMin'         => 1300,
                'precioMay'         => 1150,
                'precio_unitario'   => 1300.00,
                'stock'             => 80,
                'estado'            => 'activo',
                'fecha_alta'        => '2026-09-12',
                'fecha_modificacion'=> null,
                'usuario_carga'     => 'Diego Ramírez',
                'usuario_modificacion' => null,
            ],
            [
                'id'                => 6,
                'codigo'            => 'P006',
                'nombre'            => 'Fideos 500g',
                'descripcion'       => 'Fideos secos 500g',
                'id_categoria'      => 3,
                'nombre_categoria'  => 'Almacén',
                'id_marca'          => 6,
                'nombre_marca'      => 'Matarazzo',
                'precioMin'         => 950,
                'precioMay'         => 850,
                'precio_unitario'   => 950.00,
                'stock'             => 100,
                'estado'            => 'activo',
                'fecha_alta'        => '2026-09-15',
                'fecha_modificacion'=> null,
                'usuario_carga'     => 'Diego Ramírez',
                'usuario_modificacion' => null,
            ],
        ];
    }

    public function buscar(int $id): ?array
    {
        foreach ($this->todos() as $p) {
            if ($p['id'] === $id) return $p;
        }
        return null;
    }

    public function codigo(int $id): string
    {
        return $this->buscar($id)['codigo'] ?? '';
    }

    public function nombre(int $id): string
    {
        return $this->buscar($id)['nombre'] ?? "Producto #{$id}";
    }

    /**
     * Índice [id => producto] para lookups rápidos.
     */
    public function indexado(): array
    {
        $out = [];
        foreach ($this->todos() as $p) {
            $out[$p['id']] = $p;
        }
        return $out;
    }
}
