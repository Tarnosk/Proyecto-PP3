<?php
declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Proveedores\Models\Proveedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    protected $table = 'COMPRA';
    protected $primaryKey = 'id_compra';
    public $timestamps = false;

    protected $fillable = [
        'numero_comprobante',
        'id_proveedor',
        'id_orden',
        'importe_total',
        'saldo_pendiente',
        'estado',
        'fecha_compra',
        'fecha_cancelacion',
        'id_usuario',
    ];

    protected $casts = [
        'id_compra' => 'integer',
        'importe_total' => 'float',
        'saldo_pendiente' => 'float',
        'fecha_compra' => 'date:Y-m-d',
        'fecha_cancelacion' => 'date:Y-m-d',
    ];

    /**
     * Estados válidos de una compra (CHECK de la tabla COMPRA).
     */
    public const ESTADOS = ['pendiente', 'parcialmente_recibida', 'completada', 'cancelada'];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'id_compra', 'id_compra');
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(Recepcion::class, 'id_compra', 'id_compra');
    }
}
