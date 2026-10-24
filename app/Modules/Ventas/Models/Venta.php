<?php

namespace App\Modules\Ventas\Models;

use App\Modules\Ventas\Enums\EstadoVenta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $table = 'venta';
    protected $primaryKey = 'id_venta';
    protected $guarded = [];
    public $timestamps = false; // ¡Muy importante! Tu tabla no tiene created_at / updated_at[cite: 14]

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'id_venta', 'id_venta');
    }

    // ------------------------------------------------------------------
    // Agregado para modificar / anular / cambiar estado (ver docs/VENTAS-BACKEND.md)
    // ------------------------------------------------------------------

    /**
     * Historial de cambios de la venta (tabla venta_historial), del más viejo al más nuevo.
     */
    public function historial(): HasMany
    {
        return $this->hasMany(VentaHistorial::class, 'id_venta', 'id_venta')->orderBy('id');
    }

    /**
     * Estado actual como enum (App\Modules\Ventas\Enums\EstadoVenta).
     *
     * Devuelve null si en la base hay un texto que no es un estado conocido.
     * No se usa un "cast" automático a propósito: con un dato raro en la
     * base, el cast haría explotar la pantalla entera; así solo se bloquean
     * las acciones sobre ESA venta.
     */
    public function estadoActual(): ?EstadoVenta
    {
        return EstadoVenta::desdeTexto($this->estado);
    }
}
