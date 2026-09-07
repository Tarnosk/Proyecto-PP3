<?php
declare(strict_types=1);

namespace App\Modules\Productos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'CATEGORIA';
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_creacion',
        'fecha_modificacion',
    ];

    protected $casts = [
        'id_categoria' => 'integer',
        'fecha_creacion' => 'date:Y-m-d',
        'fecha_modificacion' => 'date:Y-m-d',
    ];

    /**
     * Productos asociados a la categoría
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_categoria', 'id_categoria');
    }
}
