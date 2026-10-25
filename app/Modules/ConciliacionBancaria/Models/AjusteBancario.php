<?php

namespace App\Modules\ConciliacionBancaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AjusteBancario extends Model
{
    protected $table = 'AJUSTE_BANCARIO';
    protected $primaryKey = 'id_ajuste';
    public $timestamps = false;

    protected $fillable = [
        'id_movimiento_bancario',
        'categoria',
        'observacion',
        'monto',
        'fecha_registro',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function movimientoBancario(): BelongsTo
    {
        return $this->belongsTo(MovimientoBancario::class, 'id_movimiento_bancario', 'id_movimiento_bancario');
    }

    public function conciliacionDetalle(): HasOne
    {
        return $this->hasOne(ConciliacionDetalle::class, 'id_ajuste', 'id_ajuste');
    }
}