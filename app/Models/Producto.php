<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    protected $table = 'productos';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'precio_mayorista' => 'decimal:2',
            'precio_minorista' => 'decimal:2',
            'stock' => 'integer',
            'stock_minimo' => 'integer',
            'estado' => ProductStatus::class,
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }
}
