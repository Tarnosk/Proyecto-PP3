<?php

namespace App\Modules\ConciliacionBancaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoConciliacion extends Model
{
    protected $table = 'PERIODO_CONCILIACION';
    protected $primaryKey = 'id_periodo';
    public $timestamps = false;

    protected $fillable = [
        'fecha_desde',
        'fecha_hasta',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'id_usuario',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function movimientosBancarios(): HasMany
    {
        return $this->hasMany(MovimientoBancario::class, 'id_periodo', 'id_periodo');
    }
}