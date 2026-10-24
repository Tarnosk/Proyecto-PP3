<?php

namespace App\Modules\Ventas\Exceptions;

/**
 * La venta pedida no existe → HTTP 404 (No encontrado).
 *
 * Ejemplo de respuesta:
 *   { "message": "La venta #999 no existe.", "codigo": "VENTA_NO_ENCONTRADA", "venta_id": 999 }
 */
class VentaNoEncontradaException extends VentaException
{
    public function __construct(private readonly int $ventaId)
    {
        parent::__construct("La venta #{$ventaId} no existe.");
    }

    public function codigoHttp(): int
    {
        return 404;
    }

    public function codigo(): string
    {
        return 'VENTA_NO_ENCONTRADA';
    }

    public function extra(): array
    {
        return ['venta_id' => $this->ventaId];
    }
}
