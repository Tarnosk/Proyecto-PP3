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
        'numero_compra',
        'numero_comprobante',
        'id_proveedor',
        'id_orden',
        'importe_total',
        'saldo_pendiente',
        'estado',
        'fecha_compra',
        'fecha_vencimiento',
        'fecha_cancelacion',
        'fecha_modificacion',
        'id_usuario',
        'id_usuario_modificacion',
    ];

    protected $casts = [
        'id_compra' => 'integer',
        'importe_total' => 'float',
        'saldo_pendiente' => 'float',
        'fecha_compra' => 'date:Y-m-d',
        'fecha_vencimiento' => 'date:Y-m-d',
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

    /**
     * Pagos registrados contra la compra (C08). N pagos por compra.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoProveedor::class, 'id_compra', 'id_compra')
            ->orderBy('fecha_pago', 'asc')
            ->orderBy('id_pago', 'asc');
    }

    public function totalPagado(): float
    {
        return round((float) $this->pagos()->sum('importe'), 2);
    }

    public function saldoFinal(): float
    {
        return round((float) $this->importe_total - $this->totalPagado(), 2);
    }
}
