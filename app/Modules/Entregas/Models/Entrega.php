<?php
namespace App\Modules\Entregas\Models;

/**
 * Modelo de Dominio: Entrega (Hoja de Ruta / Viaje de Reparto)
 */
class Entrega
{
    public ?int $idEntrega;
    public int $idRepartidor;
    public string $fechaSalida;
    public string $estado;
    public ?string $observaciones;
    public int $idUsuarioCreacion;
    public array $pedidos = [];
    public array $remitos = [];

    public function __construct(
        int $idRepartidor,
        string $fechaSalida,
        int $idUsuarioCreacion,
        string $estado = 'en_preparacion',
        ?string $observaciones = null,
        ?int $idEntrega = null
    ) {
        $this->idRepartidor = $idRepartidor;
        $this->fechaSalida = $fechaSalida;
        $this->idUsuarioCreacion = $idUsuarioCreacion;
        $this->estado = $estado;
        $this->observaciones = $observaciones;
        $this->idEntrega = $idEntrega;
    }

    public static function fromArray(array $datos): self
    {
        return new self(
            (int)($datos['id_repartidor'] ?? 0),
            $datos['fecha_salida'] ?? date('Y-m-d'),
            (int)($datos['id_usuario_creacion'] ?? 1),
            $datos['estado'] ?? 'en_preparacion',
            $datos['observaciones'] ?? null,
            isset($datos['id_entrega']) ? (int)$datos['id_entrega'] : null
        );
    }
}
