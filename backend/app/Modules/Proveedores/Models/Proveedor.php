<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'PROVEEDOR';
    protected $primaryKey = 'id_proveedor';
    public $timestamps = false;

    protected $fillable = [
        'razon_social',
        'cuit',
        'telefono',
        'email',
        'estado',
        'fecha_alta',
        'fecha_modificacion',
        'fecha_desactivacion',
        'plazo_entrega_dias',
        'id_usuario_carga',
        'id_usuario_modificacion',
    ];

    protected $casts = [
        'id_proveedor' => 'integer',
        'fecha_alta' => 'date:Y-m-d',
        'fecha_modificacion' => 'date:Y-m-d',
        'fecha_desactivacion' => 'date:Y-m-d',
        'plazo_entrega_dias' => 'integer',
    ];

    /**
     * Asociaciones producto-proveedor del proveedor (PV05)
     */
    public function productos(): HasMany
    {
        return $this->hasMany(ProductoProveedor::class, 'id_proveedor', 'id_proveedor');
    }

    /**
     * Historial de plazos de entrega (PV07). El valor vigente sigue estando en
     * la propia fila; esto es solo el rastro de cambios.
     */
    public function historialPlazos(): HasMany
    {
        return $this->hasMany(HistorialPlazoProveedor::class, 'id_proveedor', 'id_proveedor')
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id_historial_plazo');
    }

    /** Último plazo registrado en el historial (el vigente debería coincidir). */
    public function ultimoPlazoRegistrado(): ?HistorialPlazoProveedor
    {
        return $this->historialPlazos()->first();
    }
}