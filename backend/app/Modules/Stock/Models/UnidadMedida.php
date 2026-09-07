<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadMedida extends Model
{
    protected $table = 'UNIDAD_MEDIDA';
    protected $primaryKey = 'id_unidad';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'nombre_unidad',
        'equivalencia_base',
        'descripcion',
    ];

    protected $casts = [
        'id_unidad' => 'integer',
        'id_producto' => 'integer',
        'equivalencia_base' => 'decimal:4',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'id_unidad', 'id_unidad');
    }
}
