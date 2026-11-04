<?php

namespace App\Modules\ConciliacionBancaria\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * Adaptador / Modelo de Cobro para Conciliación Bancaria.
 * Mapea a la tabla cobros del ERP o rendicion_cobro como fallback.
 */
class Cobro extends Model
{
    protected $table = 'cobros';
    protected $primaryKey = 'id';

    protected $fillable = [
        'cliente_id',
        'usuario_id',
        'fecha',
        'monto_total',
        'medio_pago',
        'comprobante_nro',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'monto_total' => 'decimal:2',
    ];

    /**
     * Accesor para compatibilidad con código existente que lee $cobro->monto
     */
    public function getMontoAttribute()
    {
        return $this->attributes['monto_total'] ?? 0;
    }

    /**
     * Accesor para compatibilidad con código existente que lee $cobro->id_cobro
     */
    public function getIdCobroAttribute()
    {
        return $this->attributes['id'] ?? null;
    }

    /**
     * Accesor para compatibilidad con código existente que lee $cobro->fecha_cobro
     */
    public function getFechaCobroAttribute()
    {
        return $this->attributes['fecha'] ?? null;
    }
}
