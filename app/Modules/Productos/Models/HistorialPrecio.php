<?php

declare(strict_types=1);

namespace App\Modules\Productos\Models;

use App\Modules\Login\Models\User;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPrecio extends Model
{
    protected $table = 'HISTORIAL_PRECIO';

    protected $primaryKey = 'id_historial';

    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'tipo_precio',
        'precio',
        'porcentaje_aumento',
        'regla_redondeo',
        'origen',
        'fecha_cambio',
        'id_usuario',
    ];

    protected $casts = [
        'id_historial' => 'integer',
        'id_producto' => 'integer',
        'tipo_precio' => 'string',
        'precio' => 'decimal:2',
        'porcentaje_aumento' => 'decimal:2',
        'fecha_cambio' => 'datetime:Y-m-d H:i:s',
        'id_usuario' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function getNombreUsuarioAttribute(): string
    {
        return $this->usuario?->nombre
            ?: ($this->user?->nombre
            ?: ($this->id_usuario ? "Usuario #{$this->id_usuario}" : 'Sistema'));
    }
}
