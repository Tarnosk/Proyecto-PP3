<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Models;

use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\Proveedor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SugerenciaCompra extends Model
{
    protected $table = 'SUGERENCIA_COMPRA';
    protected $primaryKey = 'id_sugerencia';
    public $timestamps = false;

    public const ESTADOS = ['pendiente', 'procesada', 'rechazada'];
    public const MOTIVOS = ['bajo_stock', 'proximo_vencer', 'agotamiento_inmediato'];

    protected $fillable = [
        'id_producto',
        'id_proveedor',
        'cantidad_sugerida',
        'cantidad_minima',
        'cantidad_maxima',
        'precio_unitario',
        'costo_total',
        'velocidad_venta_diaria',
        'plazo_entrega_dias',
        'fecha_reorden',
        'estado',
        'motivo_generacion',
        'observaciones',
        'motivo_rechazo',
        'fecha_generacion',
        'fecha_resolucion',
        'id_usuario_resolucion',
    ];

    protected $casts = [
        'id_sugerencia' => 'integer',
        'id_producto' => 'integer',
        'id_proveedor' => 'integer',
                'id_orden_compra' => 'integer',
        'cantidad_sugerida' => 'integer',
        'cantidad_minima' => 'integer',
        'cantidad_maxima' => 'integer',
        'precio_unitario' => 'float',
        'costo_total' => 'float',
        'velocidad_venta_diaria' => 'float',
        'plazo_entrega_dias' => 'integer',
        'fecha_reorden' => 'date:Y-m-d',
        'fecha_generacion' => 'datetime',
        'fecha_resolucion' => 'datetime',
        'id_usuario_resolucion' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', 'pendiente');
    }
}
