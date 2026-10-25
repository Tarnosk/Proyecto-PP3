<?php

namespace App\Modules\Ventas\Contracts;
//No borrar
/**
 * CONTRATO PARA AVISARLE AL MÓDULO DE STOCK (de otro grupo)
 *
 * Cada vez que una venta cambia las cantidades (se modifica, se anula o se
 * restaura), Ventas le avisa a Stock cuánto tiene que sumar o restar.
 *
 * Implementaciones:
 *   - App\Modules\Ventas\Integraciones\MockStockApi    → no toca stock real, solo anota (ACTIVA hoy)
 *   - App\Modules\Ventas\Integraciones\HttpStockApi    → API real (lista para cuando se integre)
 *
 * Convención de signos (IMPORTANTE):
 *   negativo = sale mercadería del depósito (se vendió)    ej: -3
 *   positivo = vuelve mercadería al depósito (se devolvió) ej: +3
 */
interface StockGateway
{
    /**
     * Informa cambios de stock.
     *
     * @param  array<int, int>  $cambios  [id_producto => cantidad con signo], ej: [1 => -2, 5 => +1]
     * @param  int  $ventaId  venta que provocó el cambio (para que Stock sepa el origen)
     * @param  string  $motivo  "modificacion", "anulacion" o "restauracion"
     */
    public function ajustar(array $cambios, int $ventaId, string $motivo): void;

    /**
     * Unidades disponibles de un producto, o null si Stock no lo conoce.
     */
    public function disponible(int $idProducto): ?int;
}
