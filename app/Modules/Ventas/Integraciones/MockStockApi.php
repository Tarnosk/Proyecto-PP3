<?php

namespace App\Modules\Ventas\Integraciones;

use App\Modules\Ventas\Contracts\StockGateway;
use App\Services\MockProductoApi;

/**
 * STOCK SIMULADO (ACTIVO HOY)
 *
 * Todavía no tenemos el módulo de Stock. Esta clase cumple el contrato
 * StockGateway pero NO descuenta nada de ningún lado: solo guarda en memoria
 * los avisos que recibió, para que los tests puedan comprobar que Ventas
 * avisó lo correcto.
 *
 * Cuando Stock esté listo: VENTAS_INTEGRACION=http en el .env y se usa
 * HttpStockApi en su lugar. No hay que tocar nada de Ventas.
 */
class MockStockApi implements StockGateway
{
    /**
     * Avisos recibidos durante este pedido: [['venta_id' => 5, 'motivo' => 'anulacion', 'cambios' => [1 => 2]], ...]
     */
    private array $avisos = [];

    public function __construct(private readonly MockProductoApi $productos) {}

    public function ajustar(array $cambios, int $ventaId, string $motivo): void
    {
        $this->avisos[] = ['venta_id' => $ventaId, 'motivo' => $motivo, 'cambios' => $cambios];
    }

    /**
     * Devuelve el stock que figura en el mock de productos (no cambia nunca).
     */
    public function disponible(int $idProducto): ?int
    {
        $producto = $this->productos->buscar($idProducto);

        return isset($producto['stock']) ? (int) $producto['stock'] : null;
    }

    /**
     * Solo para tests: los avisos que se recibieron.
     */
    public function avisos(): array
    {
        return $this->avisos;
    }
}
