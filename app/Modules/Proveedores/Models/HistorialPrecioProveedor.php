<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Models;

use App\Modules\Login\Models\User;
use App\Modules\Compras\Models\Compra;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\UnidadMedida;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPrecioProveedor extends Model
{
    protected $table = 'HISTORIAL_PRECIO_PROVEEDOR';
    protected $primaryKey = 'id_historial_prov';
    public $timestamps = false;

    protected $fillable = [
        'id_producto_proveedor',
        'id_proveedor',
        'id_producto',
        'id_compra',
        'id_unidad',
        'precio_acordado',
        'fecha_actualizacion',
        'observaciones',
        'id_usuario',
    ];

    protected $casts = [
        'id_historial_prov' => 'integer',
        'id_producto_proveedor' => 'integer',
        'id_proveedor' => 'integer',
        'id_producto' => 'integer',
        'id_compra' => 'integer',
        'id_unidad' => 'integer',
        'precio_acordado' => 'float',
        'fecha_actualizacion' => 'datetime:Y-m-d H:i:s',
        'id_usuario' => 'integer',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function productoProveedor(): BelongsTo
    {
        return $this->belongsTo(ProductoProveedor::class, 'id_producto_proveedor', 'id_producto_proveedor');
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad', 'id_unidad');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}
