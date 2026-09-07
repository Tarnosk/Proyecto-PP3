<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Models;

use App\Modules\Proveedores\Models\Proveedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    protected $table = 'ORDEN_COMPRA';
    protected $primaryKey = 'id_orden';
    public $timestamps = false;

    protected $fillable = [
        'numero_orden',
        'id_proveedor',
        'total_estimado',
        'estado',
        'fecha_creacion',
        'fecha_modificacion',
        'fecha_envio',
        'fecha_cancelacion',
        'id_usuario',
    ];

    protected $casts = [
        'id_orden' => 'integer',
        'total_estimado' => 'float',
        'fecha_creacion' => 'date:Y-m-d',
        'fecha_modificacion' => 'date:Y-m-d',
        'fecha_envio' => 'date:Y-m-d',
        'fecha_cancelacion' => 'date:Y-m-d',
    ];

    /**
     * Estados válidos de una orden de compra (CHECK de la tabla ORDEN_COMPRA).
     */
    public const ESTADOS = ['pendiente', 'enviada', 'recibida_parcialmente', 'completada', 'cancelada'];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleOrden::class, 'id_orden', 'id_orden');
    }
}
