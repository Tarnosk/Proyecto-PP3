<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class SesionConteo extends Model
{
    protected $table = 'sesion_conteo';
    protected $primaryKey = 'id_sesion';
    public $timestamps = false;

    protected $fillable = [
        'estado',
        'fecha_conteo',
        'observaciones',
        'total_productos',
        'total_diferencias',
        'id_usuario',
    ];

    protected $casts = [
        'id_sesion' => 'integer',
        'fecha_conteo' => 'date',
        'total_productos' => 'integer',
        'total_diferencias' => 'integer',
        'id_usuario' => 'integer',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleConteo::class, 'id_sesion', 'id_sesion');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function movimientos(): HasMany
    {
        if (Schema::hasColumn('MOVIMIENTO_STOCK', 'id_sesion_conteo')) {
            return $this->hasMany(MovimientoStock::class, 'id_sesion_conteo', 'id_sesion');
        }

        return $this->hasMany(MovimientoStock::class, 'id_producto', 'id_producto');
    }
}
