<?php
declare(strict_types=1);

namespace App\Modules\Productos\Models;

use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Stock\Models\UnidadMedida;
use App\Modules\Proveedores\Models\ProductoProveedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Producto extends Model
{
    protected $table = 'PRODUCTO';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'precio_unitario',
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
        'precio_unitario' => 'decimal:2',
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

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'id_producto', 'id_producto');
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
