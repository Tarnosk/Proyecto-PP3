<?php
declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCompra extends Model
{
    protected $table = 'DETALLE_COMPRA';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_compra',
        'id_producto',
        'id_unidad',
        'cantidad',
        'cantidad_recibida',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'id_detalle' => 'integer',
        'cantidad' => 'integer',
        'cantidad_recibida' => 'integer',
        'precio_unitario' => 'float',
        'subtotal' => 'float',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
