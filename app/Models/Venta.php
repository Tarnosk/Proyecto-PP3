<?php

namespace App\Models;

use App\Enums\SaleStatus;
use Database\Factories\VentaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    /** @use HasFactory<VentaFactory> */
    use HasFactory;

    protected $table = 'ventas';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'total' => 'decimal:2',
            'estado' => SaleStatus::class,
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function cobros(): BelongsToMany
    {
        return $this->belongsToMany(Cobro::class, 'cobro_ventas', 'venta_id', 'cobro_id')
            ->withPivot('monto_aplicado');
    }
}
