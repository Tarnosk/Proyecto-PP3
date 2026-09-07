<?php
declare(strict_types=1);

namespace App\Modules\Productos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    protected $table = 'MARCA';
    protected $primaryKey = 'id_marca';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_creacion',
        'fecha_modificacion',
    ];

    protected $casts = [
        'id_marca' => 'integer',
        'fecha_creacion' => 'date:Y-m-d',
        'fecha_modificacion' => 'date:Y-m-d',
    ];

    /**
     * Productos asociados a la marca
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_marca', 'id_marca');
    }
}
