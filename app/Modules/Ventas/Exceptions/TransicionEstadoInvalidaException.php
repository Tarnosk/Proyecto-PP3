<?php

namespace App\Modules\Ventas\Exceptions;

/**
 * El cambio de estado pedido no está permitido → HTTP 409 (Conflicto).
 *
 * Ejemplos:
 *   - "facturada" → "pendiente" (no se puede volver atrás).
 *   - "confirmada" → "facturada" (primero tiene que estar pagada).
 *   - cualquier estado → "cancelada" por PATCH (para anular se usa DELETE).
 *
 * La respuesta incluye "transiciones_posibles": a qué estados SÍ se puede pasar.
 */
class TransicionEstadoInvalidaException extends VentaException
{
    /**
     * @param  list<string>  $transicionesPosibles
     */
    public function __construct(
        private readonly int $ventaId,
        private readonly string $estadoActual,
        private readonly string $estadoPedido,
        private readonly array $transicionesPosibles,
        ?string $mensaje = null,
    ) {
        parent::__construct($mensaje ?? "La venta #{$ventaId} no puede pasar de \"{$estadoActual}\" a \"{$estadoPedido}\".");
    }

    public function codigoHttp(): int
    {
        return 409;
    }

    public function codigo(): string
    {
        return 'TRANSICION_INVALIDA';
    }

    public function extra(): array
    {
        return [
            'venta_id' => $this->ventaId,
            'estado_actual' => $this->estadoActual,
            'estado_pedido' => $this->estadoPedido,
            'transiciones_posibles' => $this->transicionesPosibles,
        ];
    }
}
