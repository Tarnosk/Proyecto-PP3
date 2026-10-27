<?php
declare(strict_types=1);

namespace App\Modules\Productos\Models;

use App\Modules\Login\Models\User;
use App\Modules\Stock\Models\Lote;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Stock\Models\Ubicacion;
use App\Modules\Stock\Models\UnidadMedida;
use App\Modules\Proveedores\Models\ProductoProveedor;
use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

#[UseFactory(ProductoFactory::class)]
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    protected $table = 'PRODUCTO';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    public function getTable(): string
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return 'productos';
        }

        return $this->table;
    }

    public function getKeyName(): string
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return 'id';
        }

        return $this->primaryKey;
    }

    public function getIdAttribute(): ?int
    {
        if (isset($this->attributes['id'])) {
            return (int) $this->attributes['id'];
        }

        return isset($this->attributes['id_producto']) ? (int) $this->attributes['id_producto'] : null;
    }

    protected $appends = [
        'stock_disponible',
        'stock_actual',
        'estado_alerta',
        'precio_unitario',
        'precio_mayorista',
        'precio_minorista',
        'precioMay',
        'precioMin',
        'unidad_medida',
        'equivalencia_unidad',
        'id_unidad_activa',
        'presentacion_texto',
    ];

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'precio_unitario',
        'precioMay',
        'precioMin',
        'imagen',
        'stock',
        'stock_minimo',
        'dias_alerta_vencimiento',
        'formato_bulto',
        'unidades_por_bulto',
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
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'dias_alerta_vencimiento' => 'integer',
        'unidades_por_bulto' => 'integer',
        'id_categoria' => 'integer',
        'id_marca' => 'integer',
        'id_usuario_carga' => 'integer',
        'id_usuario_modificacion' => 'integer',
        'fecha_alta' => 'datetime:Y-m-d H:i:s',
        'fecha_modificacion' => 'datetime:Y-m-d H:i:s',
        'fecha_desactivacion' => 'datetime:Y-m-d H:i:s',
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
        $columnas = Schema::getConnection()->getSchemaBuilder()->getColumnListing('producto');
        $normalizados = [];

        foreach ($attributes as $clave => $valor) {
            $claveNormalizada = $clave;

            if ($clave === 'precio_unitario' && !in_array('precio_unitario', $columnas, true)) {
                if (in_array('precioMay', $columnas, true)) {
                    $normalizados['precioMay'] = $valor;
                }
                if (in_array('precioMin', $columnas, true)) {
                    $normalizados['precioMin'] = $valor;
                }
                continue;
            }

            if ($clave === 'precioMay' && !in_array('precioMay', $columnas, true) && in_array('precio_unitario', $columnas, true)) {
                $claveNormalizada = 'precio_unitario';
            }

            if ($clave === 'precioMin' && !in_array('precioMin', $columnas, true) && in_array('precio_unitario', $columnas, true)) {
                $claveNormalizada = 'precio_unitario';
            }

            if (!in_array($claveNormalizada, $columnas, true) && !in_array($claveNormalizada, ['precio_unitario', 'precioMay', 'precioMin', 'stock_disponible', 'stock_actual'], true)) {
                continue;
            }

            $normalizados[$claveNormalizada] = $valor;
        }

        if (!array_key_exists('fecha_alta', $normalizados) && in_array('fecha_alta', $columnas, true)) {
            $normalizados['fecha_alta'] = now()->toDateString();
        }

        if (!array_key_exists('id_usuario_carga', $normalizados) && in_array('id_usuario_carga', $columnas, true)) {
            $normalizados['id_usuario_carga'] = 1;
        }

        return $normalizados;
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca', 'id_marca');
    }

    public function usuarioCarga(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_carga', 'id');
    }

    public function usuarioModificacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_modificacion', 'id');
    }

    /**
     * Stock disponible. En el esquema fusionado el stock vive embebido en
     * PRODUCTO.stock (OB3), por lo que este atributo expone la columna directa.
     */
    public function getStockDisponibleAttribute(?int $value = null): int
    {
        return (int) ($value ?? $this->stock);
    }

    public function getStockActualAttribute(?int $value = null): int
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
     * Precio unitario de catalogo base.
     */
    public function getPrecioUnitarioAttribute(): float
    {
        return (float) ($this->attributes['precio_unitario'] ?? $this->attributes['precioMin'] ?? $this->attributes['precioMay'] ?? 0.0);
    }

    public function getPrecioMayoristaAttribute(): float
    {
        return (float) ($this->attributes['precio_unitario'] ?? $this->attributes['precioMay'] ?? 0.0);
    }

    public function getPrecioMinoristaAttribute(): float
    {
        return (float) ($this->attributes['precio_unitario'] ?? $this->attributes['precioMin'] ?? 0.0);
    }

    public function getPrecioMayAttribute(): float
    {
        return (float) ($this->attributes['precio_unitario'] ?? $this->attributes['precioMay'] ?? 0.0);
    }

    public function getPrecioMinAttribute(): float
    {
        return (float) ($this->attributes['precio_unitario'] ?? $this->attributes['precioMin'] ?? 0.0);
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

    public function getUnidadMedidaAttribute(): string
    {
        $u = $this->relationLoaded('unidades')
            ? $this->unidades->firstWhere('es_base', 1)
            : $this->unidades()->where('es_base', 1)->first();
        if ($u) {
            return $u->nombre_unidad;
        }

        $formato = $this->attributes['formato_bulto'] ?? null;
        $unidades = (int) ($this->attributes['unidades_por_bulto'] ?? 1);
        if ($formato && $formato !== 'Unidad' && $unidades > 1) {
            return "{$formato} x {$unidades}";
        }

        return 'u.';
    }

    public function getEquivalenciaUnidadAttribute(): float
    {
        $u = $this->relationLoaded('unidades')
            ? $this->unidades->firstWhere('es_base', 1)
            : $this->unidades()->where('es_base', 1)->first();
        if ($u) {
            return (float) $u->equivalencia_base;
        }

        return (float) max(1, (int) ($this->attributes['unidades_por_bulto'] ?? 1));
    }

    public function getPresentacionTextoAttribute(): string
    {
        $formato = $this->attributes['formato_bulto'] ?? 'Unidad';
        $unidades = (int) ($this->attributes['unidades_por_bulto'] ?? 1);

        if ($formato && $formato !== 'Unidad' && $unidades > 1) {
            return "{$formato} x {$unidades} u.";
        }

        return 'Unidad individual (u.)';
    }

    public function getIdUnidadActivaAttribute(): ?int
    {
        $u = $this->relationLoaded('unidades')
            ? $this->unidades->firstWhere('es_base', 1)
            : $this->unidades()->where('es_base', 1)->first();
        return $u ? (int) $u->id_unidad : null;
    }

    /**
     * Asociaciones proveedor-producto del producto (PV05)
     */
    public function proveedores(): HasMany
    {
        return $this->hasMany(ProductoProveedor::class, 'id_producto', 'id_producto');
    }

    /**
     * Lotes y vencimientos del producto (S09)
     */
    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'id_producto', 'id_producto');
    }

    /**
     * Ubicaciones físicas asignadas al producto (S14)
     */
    public function ubicaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Ubicacion::class,
            'producto_ubicacion',
            'id_producto',
            'id_ubicacion'
        )->withPivot(['fecha_asignacion', 'id_usuario']);
    }

    /**
     * Scope para bǧsqueda flexible por c��digo, nombre o descripci��n (P05)
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term && trim($term) !== '') {
            $term = trim($term);
            return $query->where(function (Builder $q) use ($term) {
                $q->where('codigo', 'LIKE', "%{$term}%")
                  ->orWhere('nombre', 'LIKE', "%{$term}%")
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
