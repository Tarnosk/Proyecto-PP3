<?php

namespace App\Modules\Ventas\Contracts;
//No borrar
/**
 * CONTRATO PARA PEDIR CLIENTES (módulo Clientes, de otro grupo)
 *
 * Igual que ProductoGateway: Ventas pide clientes a través de este contrato
 * y no le importa de dónde vienen.
 *
 * Implementaciones:
 *   - App\Services\MockClienteApi                   → datos de prueba (ACTIVA hoy)
 *   - App\Modules\Ventas\Integraciones\HttpClienteApi     → API real (lista para cuando se integre)
 *
 * Formato de cada cliente que Ventas necesita:
 *   ['id' => 472891, 'nombre' => 'Supermercado El Norte SRL', 'cuit' => '30-71234567-8']
 */
interface ClienteGateway
{
    /**
     * Todos los clientes.
     *
     * @return list<array>
     */
    public function todos(): array;

    /**
     * Un cliente por id, o null si no existe.
     */
    public function buscar(int $id): ?array;
}
