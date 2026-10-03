<?php
declare(strict_types=1);

namespace App\Modules\Compras\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * C08 - Registrar múltiples métodos de pago de una compra.
 *
 * Una fila por método de pago: la compra puede recibir cuantos pagos haga falta,
 * incluso del mismo método. El saldo de la compra se recalcula a partir de la
 * suma de estas filas (ver PagoCompraService).
 */
class PagoProveedor extends Model
{
    protected $table = 'PAGO_PROVEEDOR';
    protected $primaryKey = 'id_pago';
    public $timestamps = false;

    /** Debe coincidir con el ENUM metodo_pago del esquema. */
    public const METODOS = ['efectivo', 'transferencia', 'cheque', 'echeq', 'tarjeta'];

    protected $fillable = [
        'id_compra',
        'metodo_pago',
        'importe',
        'fecha_pago',
        'id_usuario',
    ];

    protected $casts = [
        'id_pago' => 'integer',
        'id_compra' => 'integer',
        'importe' => 'float',
        'fecha_pago' => 'date:Y-m-d',
        'id_usuario' => 'integer',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }
}