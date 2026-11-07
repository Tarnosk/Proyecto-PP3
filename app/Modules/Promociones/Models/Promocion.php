<?php

namespace App\Modules\Promociones\Models;

use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    // 1. Nombre exacto de la tabla en tu base de datos (en minúsculas)
    protected $table = 'promocion';
    protected $primaryKey = 'id_promocion';

    // ¡SÚPER IMPORTANTE! Descomentamos esta línea porque tu tabla no tiene timestamps
    public $timestamps = false;

    // Campos que se pueden llenar masivamente (Actualizados a los nombres reales de tu BD)
    protected $fillable = [
        'nombre',
        'descripcion', // En tu BD se llama 'descripcion', no 'condiciones'
        'total',
        'tipo_descuento',
        'valor_descuento',
        'vigencia_desde',
        'vigencia_hasta',
        'estado'
    ];

    // 2. Relación con Producto
    public function productos()
    {
        // Usamos los nombres en minúsculas y agregamos withPivot para la cantidad
        return $this->belongsToMany(Producto::class, 'promocion_producto', 'id_promocion', 'id_producto')
            ->withPivot('cantidad');
    }
}
