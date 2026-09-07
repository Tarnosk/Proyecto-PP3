<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleOrden extends Model
{
    protected $table = 'DETALLE_ORDEN';
    protected $primaryKey = 'id_detalle_orden';
    public $timestamps = false;

    protected $fillable = [
        'id_orden',
        'id_producto',
        'id_unidad',
        'cantidad_solicitada',
        'cantidad_sugerida',
        'origen',
        'precio_estimado',
        'subtotal',
    ];

    protected $casts = [
        'id_detalle_orden' => 'integer',
        'cantidad_solicitada' => 'integer',
        'cantidad_sugerida' => 'integer',
        'precio_estimado' => 'float',
        'subtotal' => 'float',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'id_orden', 'id_orden');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
