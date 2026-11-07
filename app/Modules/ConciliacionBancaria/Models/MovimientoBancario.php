<?php

namespace App\Modules\ConciliacionBancaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoBancario extends Model
{
    protected $table = 'MOVIMIENTO_BANCARIO';
    protected $primaryKey = 'id_movimiento_bancario';

    protected $fillable = [
        'id_archivo_importado_log',
        'fecha_operacion',
        'monto',
        'signo_monto',
        'descripcion_original',
        'descripcion_normalizada',
        'nro_comprobante_extraido',
        'cuit_extraido',
        'tipo_movimiento',
        'estado_conciliacion',
    ];

    protected $casts = [
        'fecha_operacion' => 'date',
        'monto' => 'decimal:2',
    ];

    public function archivoImportado(): BelongsTo
    {
        return $this->belongsTo(ArchivoImportadoLog::class, 'id_archivo_importado_log', 'id_archivo_importado_log');
    }
}
