<?php
declare(strict_types=1);

namespace App\Modules\Productos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPrecio extends Model
{
    protected $table = 'HISTORIAL_PRECIO';
    protected $primaryKey = 'id_historial';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'precio_anterior',
        'precio_nuevo',
        'porcentaje_aumento',
        'regla_redondeo',
        'origen',
        'fecha_cambio',
        'id_usuario',
    ];

    protected $casts = [
        'id_historial' => 'integer',
        'id_producto' => 'integer',
        'precio_anterior' => 'decimal:2',
        'precio_nuevo' => 'decimal:2',
        'porcentaje_aumento' => 'decimal:2',
        'fecha_cambio' => 'date:Y-m-d',
        'id_usuario' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
