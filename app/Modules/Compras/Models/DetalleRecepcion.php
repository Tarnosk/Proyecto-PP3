<?php
declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleRecepcion extends Model
{
    protected $table = 'DETALLE_RECEPCION';
    protected $primaryKey = 'id_det_rec';
    public $timestamps = false;

    protected $fillable = [
        'id_recepcion',
        'id_producto',
        'id_lote',
        'id_unidad',
        'cantidad_recibida',
    ];

    protected $casts = [
        'id_det_rec' => 'integer',
        'cantidad_recibida' => 'integer',
    ];

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(Recepcion::class, 'id_recepcion', 'id_recepcion');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
