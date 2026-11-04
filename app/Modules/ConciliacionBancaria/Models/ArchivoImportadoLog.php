<?php

namespace App\Modules\ConciliacionBancaria\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivoImportadoLog extends Model
{
    protected $table = 'ARCHIVO_IMPORTADO_LOG';
    protected $primaryKey = 'id_archivo_importado_log';

    protected $fillable = [
        'nombre_original',
        'ruta_almacenada',
        'hash_sha256',
        'formato_extension',
        'cantidad_filas_leidas',
        'cantidad_filas_insertadas',
        'cantidad_filas_omitidas',
        'cantidad_filas_error',
        'id_usuario',
        'saldo_inicial',
        'saldo_final',
        'fecha_desde',
        'fecha_hasta',
    ];

    protected $casts = [
        'cantidad_filas_leidas' => 'integer',
        'cantidad_filas_insertadas' => 'integer',
        'cantidad_filas_omitidas' => 'integer',
        'cantidad_filas_error' => 'integer',
        'saldo_inicial' => 'decimal:2',
        'saldo_final' => 'decimal:2',
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
    ];

    public function movimientos()
    {
        return $this->hasMany(MovimientoBancario::class, 'id_archivo_importado_log', 'id_archivo_importado_log');
    }
}
