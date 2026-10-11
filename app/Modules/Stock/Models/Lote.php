<?php
declare(strict_types=1);

namespace App\Modules\Stock\Models;

use App\Modules\Productos\Models\Producto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lote extends Model
{
    protected $table = 'LOTE';
    protected $primaryKey = 'id_lote';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'nro_lote',
        'cantidad_inicial',
        'cantidad_actual',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'id_lote'           => 'integer',
        'id_producto'       => 'integer',
        'cantidad_inicial'  => 'integer',
        'cantidad_actual'   => 'integer',
        'fecha_vencimiento' => 'date:Y-m-d',
    ];

    protected $appends = [
        'dias_para_vencer',
        'estado',
    ];

    // ──────────────────────────────────────────────
    // RELACIONES
    // ──────────────────────────────────────────────

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    /**
     * El movimiento de ingreso se obtiene desde MOVIMIENTO_STOCK.id_lote
     * (relación inversa: MOVIMIENTO_STOCK tiene la FK, no LOTE).
     */
    public function movimiento(): HasOne
    {
        return $this->hasOne(MovimientoStock::class, 'id_lote', 'id_lote')
                    ->where('tipo', 'ingreso');
    }

    // ──────────────────────────────────────────────
    // ATRIBUTOS CALCULADOS
    // ──────────────────────────────────────────────

    /** Días hasta vencimiento (negativo si ya venció). */
    public function getDiasParaVencerAttribute(): ?int
    {
        if (!$this->fecha_vencimiento) {
            return null;
        }
        return (int) Carbon::today()->diffInDays($this->fecha_vencimiento, false);
    }

    /**
     * Estado derivado del vencimiento y stock actual.
     * 'vigente' | 'vencido' | 'consumido'
     * No se almacena en BD; se calcula en tiempo real.
     */
    public function getEstadoAttribute(): string
    {
        if ((int) $this->cantidad_actual <= 0) {
            return 'consumido';
        }
        $dias = $this->getDiasParaVencerAttribute();
        return ($dias !== null && $dias < 0) ? 'vencido' : 'vigente';
    }

    // ──────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────

    public function scopePorProducto(Builder $query, int $idProducto): Builder
    {
        return $query->where('id_producto', $idProducto);
    }

    /** Lotes no consumidos (cantidad > 0) y no vencidos. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('cantidad_actual', '>', 0)
                     ->where('fecha_vencimiento', '>=', Carbon::today()->toDateString());
    }

    /**
     * Lotes con cantidad pendiente y fecha de vencimiento ya pasada.
     * replica exactamente la precedencia de getEstadoAttribute(), donde
     * 'consumido' gana sobre 'vencido'.
     */
    public function scopeVencidos(Builder $query): Builder
    {
        return $query->where('cantidad_actual', '>', 0)
                     ->where('fecha_vencimiento', '<', Carbon::today()->toDateString());
    }

    /** Lotes vigentes que vencen en los próximos N días. */
    public function scopePorVencer(Builder $query, int $dias = 30): Builder
    {
        $hoy    = Carbon::today()->toDateString();
        $limite = Carbon::today()->addDays($dias)->toDateString();
        return $query->where('cantidad_actual', '>', 0)
                     ->whereBetween('fecha_vencimiento', [$hoy, $limite]);
    }

    /** Búsqueda por nro_lote o código/descripción de producto. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term && trim($term) !== '') {
            $term = trim($term);
            return $query->where(function (Builder $q) use ($term) {
                $q->where('nro_lote', 'LIKE', "%{$term}%")
                  ->orWhereHas('producto', function (Builder $qp) use ($term) {
                      $qp->where('codigo', 'LIKE', "%{$term}%")
                         ->orWhere('descripcion', 'LIKE', "%{$term}%");
                  });
            });
        }
        return $query;
    }
}