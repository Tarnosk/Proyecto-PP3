<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PV07 - Historial de modificaciones del plazo de entrega de un proveedor.
 *
 * PROVEEDOR.plazo_entrega_dias guarda solo el valor vigente; cada cambio queda
 * registrado acá con el valor anterior, el nuevo, la fecha y el usuario.
 * plazo_anterior_dias es null cuando el proveedor todavía no tenía plazo.
 */
class HistorialPlazoProveedor extends Model
{
    protected $table = 'HISTORIAL_PLAZO_PROVEEDOR';
    protected $primaryKey = 'id_historial_plazo';
    public $timestamps = false;

    protected $fillable = [
        'id_proveedor',
        'plazo_anterior_dias',
        'plazo_nuevo_dias',
        'fecha_cambio',
        'id_usuario',
    ];

    protected $casts = [
        'id_historial_plazo' => 'integer',
        'id_proveedor' => 'integer',
        'plazo_anterior_dias' => 'integer',
        'plazo_nuevo_dias' => 'integer',
        'fecha_cambio' => 'datetime',
        'id_usuario' => 'integer',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }
}