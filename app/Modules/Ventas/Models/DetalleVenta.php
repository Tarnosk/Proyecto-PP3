<?php

namespace App\Modules\Ventas\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalle_venta';
    protected $primaryKey = 'id_detalle';
    protected $guarded = [];
    public $timestamps = false; // Igual aquí, tu tabla no tiene timestamps[cite: 13]

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta', 'id_venta');
    }
}
