<?php

namespace App\Modules\Ventas\Exceptions;

use Throwable;

/**
 * Otro módulo (Productos, Clientes, Stock) no respondió → HTTP 503 (Servicio no disponible).
 *
 * Solo puede pasar cuando VENTAS_INTEGRACION=http (APIs reales de otros grupos).
 * Con los mocks actuales nunca ocurre.
 *
 * Ejemplo de respuesta:
 *   { "message": "El módulo de productos no está disponible. Probá de nuevo en unos minutos.",
 *     "codigo": "INTEGRACION_NO_DISPONIBLE", "servicio": "productos" }
 */
class IntegracionNoDisponibleException extends VentaException
{
    public function __construct(private readonly string $servicio, ?Throwable $causa = null)
    {
        parent::__construct(
            "El módulo de {$servicio} no está disponible. Probá de nuevo en unos minutos.",
            0,
            $causa // se guarda el error original (timeout, 500, etc.) para el log
        );
    }

    public function codigoHttp(): int
    {
        return 503;
    }

    public function codigo(): string
    {
        return 'INTEGRACION_NO_DISPONIBLE';
    }

    public function extra(): array
    {
        return ['servicio' => $this->servicio];
    }
}
