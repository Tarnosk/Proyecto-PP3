<?php

namespace App\Modules\Rendiciones\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent: RendicionCobro
 * Tabla: RENDICION_COBRO
 *
 * Registra cada cobro individual realizado durante un reparto,
 * asociado a su rendición, cliente y factura correspondiente.
 */
class RendicionCobro extends Model
{
    protected $table      = 'RENDICION_COBRO';
    protected $primaryKey = 'id_rendicion_cobro';
    public $timestamps    = false;

    protected $fillable = [
        'id_rendicion',
        'id_cliente',
        'id_factura',       // FK a la factura/venta que se está pagando
        'monto',
        'medio_pago',       // efectivo | transferencia | cheque
        'fecha_registro',
    ];

    protected $casts = [
        'id_rendicion_cobro' => 'integer',
        'id_rendicion'       => 'integer',
        'id_cliente'         => 'integer',
        'id_factura'         => 'integer',
        'monto'              => 'float',
        'fecha_registro'     => 'datetime',
    ];

    // --- RELACIONES ELOQUENT ---

    /**
     * Rendición a la que pertenece este cobro.
     */
    public function rendicion(): BelongsTo
    {
        return $this->belongsTo(Rendicion::class, 'id_rendicion', 'id_rendicion');
    }
}
