<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Modules\Login\Models\User;
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
        'cantidad_base',
        'fecha',
        'motivo',
        'id_usuario',
        'id_venta',
        'id_entrega',
        'id_recepcion',
        'id_sesion_conteo',
    ];

    protected $casts = [
        'id_movimiento' => 'integer',
        'id_producto' => 'integer',
        'id_unidad' => 'integer',
        'cantidad' => 'integer',
        'cantidad_base' => 'integer',
        'fecha' => 'datetime:Y-m-d H:i:s',
        'id_usuario' => 'integer',
        'id_venta' => 'integer',
        'id_entrega' => 'integer',
        'id_recepcion' => 'integer',
        'id_sesion_conteo' => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad', 'id_unidad');
    }
}
