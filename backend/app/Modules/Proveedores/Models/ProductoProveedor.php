<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoProveedor extends Model
{
    protected $table = 'PRODUCTO_PROVEEDOR';
    protected $primaryKey = 'id_producto_proveedor';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_proveedor',
        'es_proveedor_principal',
        'precio_acordado',
        'activo',
        'fecha_asociacion',
        'fecha_desasociacion',
        'id_usuario',
    ];

    protected $casts = [
        'id_producto_proveedor' => 'integer',
        'id_producto' => 'integer',
        'id_proveedor' => 'integer',
        'es_proveedor_principal' => 'boolean',
        'precio_acordado' => 'decimal:2',
        'activo' => 'boolean',
        'fecha_asociacion' => 'date:Y-m-d',
        'fecha_desasociacion' => 'date:Y-m-d',
        'id_usuario' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }
}
