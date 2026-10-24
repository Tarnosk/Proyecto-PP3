<?php

namespace App\Modules\Ventas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODELO de la tabla venta_historial (auditoría de cada venta).
 *
 * Un "modelo" es la clase que representa una tabla: permite leer y guardar
 * filas sin escribir SQL a mano (VentaHistorial::create([...]), etc.).
 *
 * Cada fila = algo que le pasó a una venta (modificación, anulación,
 * restauración o cambio de estado). Ver la migración
 * app/Modules/Ventas/database/migrations/2026_10_16_000000_create_venta_historial_table.php
 */
class VentaHistorial extends Model
{
    // Las acciones posibles (se guardan en la columna "accion").
    public const MODIFICACION = 'modificacion';

    public const ANULACION = 'anulacion';

    public const RESTAURACION = 'restauracion';

    public const CAMBIO_ESTADO = 'cambio_estado';

    protected $table = 'venta_historial';

    // Solo tiene created_at (no updated_at): una fila de historial nunca se edita.
    public const UPDATED_AT = null;

    protected $fillable = [
        'id_venta',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'datos',
        'id_usuario',
    ];

    protected $casts = [
        'datos' => 'array',              // la columna JSON se lee como array de PHP
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * La venta a la que pertenece esta fila.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }
}
