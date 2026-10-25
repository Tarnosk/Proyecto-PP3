<?php

namespace App\Modules\Ventas\Contracts;


//No borrar: Es la lógica guía para cuando se haga la integración de los demas módulos.


/**
 * CONTRATO PARA PEDIR PRODUCTOS (módulo Productos, de otro grupo)
 *
 * Una "interface" es una promesa: dice QUÉ métodos tiene que tener una clase,
 * pero no CÓMO los hace. Ventas solo conoce este contrato, no sabe si los
 * productos vienen de un mock o de una API real.
 *
 * Implementaciones:
 *   - App\Services\MockProductoApi                  → datos de prueba (ACTIVA hoy)
 *   - App\Modules\Ventas\Integraciones\HttpProductoApi    → API real (lista para cuando se integre)
 *
 * Cuál se usa lo decide VENTAS_INTEGRACION en el .env (ver config/ventas.php
 * y app/Providers/AppServiceProvider.php).
 *
 * Formato de cada producto que Ventas necesita:
 *   ['id' => 1, 'codigo' => 'P001', 'nombre' => 'Coca-Cola 1.5L', 'precioMin' => 2500, 'precioMay' => 2200, 'stock' => 48]
 */
interface ProductoGateway
{
    /**
     * Todos los productos.
     *
     * @return list<array>
     */
    public function todos(): array;

    /**
     * Un producto por id, o null si no existe.
     */
    public function buscar(int $id): ?array;
}
