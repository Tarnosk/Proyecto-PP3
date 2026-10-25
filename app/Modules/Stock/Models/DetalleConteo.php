<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleConteo extends Model
{
    protected $table = 'detalle_conteo';
    protected $primaryKey = 'id_detalle_conteo';
    public $timestamps = false;

    protected $fillable = [
        'id_sesion',
        'id_producto',
        'cantidad_fisica',
        'stock_sistema',
        'diferencia',
        'observaciones',
    ];

    protected $casts = [
        'id_detalle_conteo' => 'integer',
        'id_sesion' => 'integer',
        'id_producto' => 'integer',
        'cantidad_fisica' => 'integer',
        'stock_sistema' => 'integer',
        'diferencia' => 'integer',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionConteo::class, 'id_sesion', 'id_sesion');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function getDiagnosticoAttribute(): string
    {
        if ($this->diferencia === 0) {
            return 'coincidente';
        }

        return $this->diferencia < 0 ? 'faltante' : 'sobrante';
    }
}
