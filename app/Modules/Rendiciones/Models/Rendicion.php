<?php

namespace App\Modules\Rendiciones\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * Modelo Eloquent: Rendicion
 * Tabla: RENDICION
 *
 * Representa la rendición de un repartidor al final de su jornada.
 * Registra todos los cobros realizados, los remitos entregados y las diferencias detectadas.
 */
class Rendicion extends Model
{
    // --- CONSTANTES DE ESTADO ---
    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_APROBADA   = 'aprobada';
    public const ESTADO_RECHAZADA  = 'rechazada';

    protected $table      = 'RENDICION';
    protected $primaryKey = 'id_rendicion';
    public $timestamps    = false;

    protected $fillable = [
        'id_repartidor',
        'id_entrega',        // FK nullable → ENTREGA (el reparto que dio origen a la rendición)
        'fecha',
        'total_rendido',
        'estado',
        'observaciones',
        'id_usuario_validador',
        'fecha_validacion',
        'motivo_rechazo',
    ];

    protected $casts = [
        'id_rendicion'        => 'integer',
        'id_repartidor'       => 'integer',
        'id_entrega'          => 'integer',
        'total_rendido'       => 'float',
        'fecha'               => 'datetime',
        'fecha_validacion'    => 'datetime',
        'id_usuario_validador' => 'integer',
    ];

    // --- RELACIONES ELOQUENT ---

    /**
     * Cobros registrados dentro de esta rendición.
     */
    public function cobros(): HasMany
    {
        return $this->hasMany(RendicionCobro::class, 'id_rendicion', 'id_rendicion');
    }

    // --- MÉTODOS DE DOMINIO ---

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function estaAprobada(): bool
    {
        return $this->estado === self::ESTADO_APROBADA;
    }

    public function estaRechazada(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADA;
    }

    /**
     * Aplica la aprobación de la rendición, validando que solo pueda aprobarse si está pendiente.
     */
    public function aprobar(int $idUsuarioValidador): void
    {
        if (!$this->estaPendiente()) {
            throw new InvalidArgumentException(
                "Solo se puede aprobar una rendición en estado pendiente. Estado actual: {$this->estado}."
            );
        }

        $this->estado               = self::ESTADO_APROBADA;
        $this->id_usuario_validador = $idUsuarioValidador;
        $this->fecha_validacion     = now();
        $this->motivo_rechazo       = null;
    }

    /**
     * Aplica el rechazo de la rendición, exigiendo motivo obligatorio.
     */
    public function rechazar(int $idUsuarioValidador, string $motivo): void
    {
        if (!$this->estaPendiente()) {
            throw new InvalidArgumentException(
                "Solo se puede rechazar una rendición en estado pendiente. Estado actual: {$this->estado}."
            );
        }

        if (empty(trim($motivo))) {
            throw new InvalidArgumentException("El motivo de rechazo es obligatorio.");
        }

        $this->estado               = self::ESTADO_RECHAZADA;
        $this->id_usuario_validador = $idUsuarioValidador;
        $this->fecha_validacion     = now();
        $this->motivo_rechazo       = $motivo;
    }
}
