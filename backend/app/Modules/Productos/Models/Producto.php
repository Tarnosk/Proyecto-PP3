<?php
declare(strict_types=1);

namespace App\Modules\Productos\Models;

use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Stock\Models\UnidadMedida;
use App\Modules\Proveedores\Models\ProductoProveedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Producto extends Model
{
    protected $table = 'PRODUCTO';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $appends = [
        'stock_disponible',
        'estado_alerta',
        'precio_unitario',
        'precio_mayorista',
        'precio_minorista',
    ];

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'precioMay',
        'precioMin',
        'imagen',
        'stock',
        'stock_minimo',
        'dias_alerta_vencimiento',
        'estado',
        'fecha_alta',
        'fecha_modificacion',
        'fecha_desactivacion',
        'id_categoria',
        'id_marca',
        'id_usuario_carga',
        'id_usuario_modificacion',
    ];

    protected $casts = [
        'id_producto' => 'integer',
        'precioMay' => 'decimal:2',
        'precioMin' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'dias_alerta_vencimiento' => 'integer',
        'id_categoria' => 'integer',
        'id_marca' => 'integer',
        'id_usuario_carga' => 'integer',
        'id_usuario_modificacion' => 'integer',
        'fecha_alta' => 'date:Y-m-d',
        'fecha_modificacion' => 'date:Y-m-d',
        'fecha_desactivacion' => 'date:Y-m-d',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca', 'id_marca');
    }

    /**
     * Stock disponible. En el esquema fusionado el stock vive embebido en
     * PRODUCTO.stock (OB3), por lo que este atributo expone la columna directa.
     */
    public function getStockDisponibleAttribute(?int $value = null): int
    {
        return (int) ($value ?? $this->stock);
    }

    public function getStockMinimoAttribute(?int $value = null): int
    {
        return (int) ($value ?? ($this->attributes['stock_minimo'] ?? 0));
    }

    /**
     * Estado de alerta derivado (S07): crítico <= 0, bajo <= mínimo (> 0), normal.
     * Ya no se persiste en una tabla STOCK separada.
     */
    public function getEstadoAlertaAttribute(): string
    {
        $disponible = $this->stock_disponible;
        $minimo = $this->stock_minimo;

        if ($disponible <= 0) {
            return 'critico';
        }
        if ($disponible <= $minimo) {
            return 'bajo';
        }
        return 'normal';
    }

    /**
     * Precio unitario (alias de compatibilidad) = precio mayorista.
     */
    public function getPrecioUnitarioAttribute(): float
    {
        return (float) $this->precioMay;
    }

    public function getPrecioMayoristaAttribute(): float
    {
        return (float) $this->precioMay;
    }

    public function getPrecioMinoristaAttribute(): float
    {
        return (float) $this->precioMin;
    }

    public function historialPrecios(): HasMany
    {
        return $this->hasMany(HistorialPrecio::class, 'id_producto', 'id_producto')
                    ->orderBy('id_historial', 'desc');
    }

    /**
     * Movimientos de stock del producto (S04, S05, S06, S12)
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'id_producto', 'id_producto')
                    ->orderBy('id_movimiento', 'desc');
    }

    /**
     * Unidades de medida y equivalencias del producto (S08)
     */
    public function unidades(): HasMany
    {
        return $this->hasMany(UnidadMedida::class, 'id_producto', 'id_producto');
    }

    /**
     * Asociaciones proveedor-producto del producto (PV05)
     */
    public function proveedores(): HasMany
    {
        return $this->hasMany(ProductoProveedor::class, 'id_producto', 'id_producto');
    }

    /**
     * Scope para búsqueda flexible por código o descripción (P05)
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term && trim($term) !== '') {
            $term = trim($term);
            return $query->where(function (Builder $q) use ($term) {
                $q->where('codigo', 'LIKE', "%{$term}%")
                  ->orWhere('descripcion', 'LIKE', "%{$term}%");
            });
        }
        return $query;
    }

    /**
     * Scope para filtrar por categoría (P05)
     */
    public function scopeFilterCategoria(Builder $query, ?int $categoriaId): Builder
    {
        if ($categoriaId && $categoriaId > 0) {
            return $query->where('id_categoria', $categoriaId);
        }
        return $query;
    }

    /**
     * Scope para filtrar por marca (P05)
     */
    public function scopeFilterMarca(Builder $query, ?int $marcaId): Builder
    {
        if ($marcaId && $marcaId > 0) {
            return $query->where('id_marca', $marcaId);
        }
        return $query;
    }

    /**
     * Scope para filtrar por estado (P05 / P06)
     */
    public function scopeFilterEstado(Builder $query, ?string $estado): Builder
    {
        if ($estado && in_array(strtolower($estado), ['activo', 'inactivo'])) {
            return $query->where('estado', strtolower($estado));
        }
        return $query;
    }
}
