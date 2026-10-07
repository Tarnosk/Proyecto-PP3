<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ubicacion extends Model
{
    protected $table = 'ubicacion';
    protected $primaryKey = 'id_ubicacion';
    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'estado',
        'fecha_creacion',
    ];

    protected $casts = [
        'id_ubicacion' => 'integer',
        'fecha_creacion' => 'date',
    ];

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(
            Producto::class,
            'producto_ubicacion',
            'id_ubicacion',
            'id_producto'
        )->withPivot(['fecha_asignacion', 'id_usuario']);
    }
}
