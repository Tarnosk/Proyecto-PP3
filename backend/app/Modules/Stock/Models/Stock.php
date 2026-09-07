<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $table = 'STOCK';
    protected $primaryKey = 'id_stock';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'stock_disponible',
        'stock_minimo',
        'estado_alerta',
        'dias_alerta_vencimiento',
    ];

    protected $casts = [
        'id_stock' => 'integer',
        'id_producto' => 'integer',
        'stock_disponible' => 'integer',
        'stock_minimo' => 'integer',
        'dias_alerta_vencimiento' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
