<?php

namespace App\Modules\ConciliacionBancaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovimientoBancario extends Model
{
    protected $table = 'MOVIMIENTO_BANCARIO';
    protected $primaryKey = 'id_movimiento_bancario';
    public $timestamps = false;

    protected $fillable = [
        'id_periodo',
        'fecha_movimiento',
        'descripcion',
        'monto',
        'tipo',
        'estado',
        'fecha_importacion',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_movimiento' => 'date',
        'fecha_importacion' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoConciliacion::class, 'id_periodo', 'id_periodo');
    }

    public function ajustes(): HasMany
    {
        return $this->hasMany(AjusteBancario::class, 'id_movimiento_bancario', 'id_movimiento_bancario');
    }

    public function conciliacionDetalle(): HasMany
    {
        return $this->hasMany(ConciliacionDetalle::class, 'id_movimiento_bancario', 'id_movimiento_bancario');
    }
}