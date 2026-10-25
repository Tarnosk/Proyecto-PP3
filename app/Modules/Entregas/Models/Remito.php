<?php
namespace App\Modules\Entregas\Models;

/**
 * Modelo de Dominio: Remito (Comprobante legal de traslado de mercadería)
 */
class Remito
{
    public ?int $idRemito;
    public string $numeroRemito;
    public int $idEntrega;
    public int $idVenta;
    public string $fechaEmision;
    public string $estado;
    public ?string $observaciones;
    public array $items = [];

    public function __construct(
        string $numeroRemito,
        int $idEntrega,
        int $idVenta,
        string $estado = 'emitido',
        ?string $observaciones = null,
        ?int $idRemito = null,
        ?string $fechaEmision = null
    ) {
        $this->numeroRemito = $numeroRemito;
        $this->idEntrega = $idEntrega;
        $this->idVenta = $idVenta;
        $this->estado = $estado;
        $this->observaciones = $observaciones;
        $this->idRemito = $idRemito;
        $this->fechaEmision = $fechaEmision ?? date('Y-m-d H:i:s');
    }
}
