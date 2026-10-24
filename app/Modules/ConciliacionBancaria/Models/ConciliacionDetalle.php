<?php

namespace App\Modules\ConciliacionBancaria\Models;

use App\Modules\Caja\Models\CajaMovimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConciliacionDetalle extends Model
{
    protected $table = 'CONCILIACION_DETALLE';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_movimiento_bancario',
        'id_cobro',
        'id_movimiento_caja',
        'id_ajuste',
        'id_cheque',
        'fecha_conciliacion',
        'metodo',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_conciliacion' => 'datetime',
    ];

    public function movimientoBancario(): BelongsTo
    {
        return $this->belongsTo(MovimientoBancario::class, 'id_movimiento_bancario', 'id_movimiento_bancario');
    }

    public function cajaMovimiento(): BelongsTo
    {
        return $this->belongsTo(CajaMovimiento::class, 'id_movimiento_caja', 'id_movimiento_caja');
    }

    public function ajuste(): BelongsTo
    {
        return $this->belongsTo(AjusteBancario::class, 'id_ajuste', 'id_ajuste');
    }

    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class, 'id_cheque', 'id_cheque');
    }

    /**
     * Regla de integridad a nivel aplicación (ya reforzada en BD con un CHECK
     * constraint, pero conviene validar acá también antes de guardar):
     * exactamente una de las 4 FK debe estar completa.
     */
    public function tieneOrigenUnico(): bool
    {
        $origenes = [$this->id_cobro, $this->id_movimiento_caja, $this->id_ajuste, $this->id_cheque];
        return count(array_filter($origenes, fn ($v) => $v !== null)) === 1;
    }
}