<?php

namespace App\Modules\Ventas\Exceptions;

/**
 * La venta existe pero su estado NO permite la acción pedida → HTTP 409 (Conflicto).
 *
 * Casos típicos:
 *   - Modificar o eliminar una venta "pagada", "facturada" o "cancelada".
 *   - Restaurar una venta que NO está anulada.
 *
 * Ejemplo de respuesta:
 *   {
 *     "message": "La venta #5 está pagada: no se puede modificar.",
 *     "codigo": "VENTA_NO_EDITABLE",
 *     "venta_id": 5,
 *     "accion": "modificar",
 *     "estado_actual": "pagada"
 *   }
 */
class VentaNoEditableException extends VentaException
{
    /**
     * @param  string  $accion  qué se intentó: "modificar", "eliminar" o "restaurar"
     * @param  string  $estadoActual  estado en el que está la venta
     */
    public function __construct(
        private readonly int $ventaId,
        private readonly string $accion,
        private readonly string $estadoActual,
        ?string $mensaje = null,
    ) {
        parent::__construct($mensaje ?? "La venta #{$ventaId} está {$estadoActual}: no se puede {$accion}.");
    }

    public function codigoHttp(): int
    {
        return 409;
    }

    public function codigo(): string
    {
        return 'VENTA_NO_EDITABLE';
    }

    public function extra(): array
    {
        return [
            'venta_id' => $this->ventaId,
            'accion' => $this->accion,
            'estado_actual' => $this->estadoActual,
        ];
    }
}
