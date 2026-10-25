<?php
declare(strict_types=1);

namespace App\Modules\Productos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Marca extends Model
{
    protected $table = 'MARCA';
    protected $primaryKey = 'id_marca';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'fecha_creacion',
        'fecha_modificacion',
        'activo',
    ];

    protected $casts = [
        'id_marca' => 'integer',
        'fecha_creacion' => 'date:Y-m-d',
        'fecha_modificacion' => 'date:Y-m-d',
    ];

    public static function create(array $attributes = [])
    {
        $attributes = static::normalizarAtributos($attributes);
        $instance = new static($attributes);
        $instance->save();

        return $instance;
    }

    protected static function normalizarAtributos(array $attributes): array
    {
        $columnas = Schema::getConnection()->getSchemaBuilder()->getColumnListing('marca');
        $normalizados = [];

        foreach ($attributes as $clave => $valor) {
            if (!in_array($clave, $columnas, true) && !in_array($clave, ['nombre', 'fecha_creacion', 'fecha_modificacion', 'activo'], true)) {
                continue;
            }

            if ($clave === 'activo' && !in_array('activo', $columnas, true)) {
                continue;
            }

            $normalizados[$clave] = $valor;
        }

        if (!array_key_exists('fecha_creacion', $normalizados) && in_array('fecha_creacion', $columnas, true)) {
            $normalizados['fecha_creacion'] = now()->toDateString();
        }

        return $normalizados;
    }

    /**
     * Productos asociados a la marca
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_marca', 'id_marca');
    }
}
