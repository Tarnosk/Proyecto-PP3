<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoStock extends Model
{
    protected $table = 'MOVIMIENTO_STOCK';
    protected $primaryKey = 'id_movimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_unidad',
        'tipo',
        'cantidad',
        'fecha',
        'motivo',
        'id_usuario',
        'id_venta',
        'id_entrega',
    ];

    protected $casts = [
        'id_movimiento' => 'integer',
        'id_producto' => 'integer',
        'id_unidad' => 'integer',
        'cantidad' => 'integer',
        'fecha' => 'date:Y-m-d',
        'id_usuario' => 'integer',
        'id_venta' => 'integer',
        'id_entrega' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad', 'id_unidad');
    }
}
