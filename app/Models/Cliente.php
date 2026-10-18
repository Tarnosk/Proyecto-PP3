<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido_razon_social',
        'dni_cuit',
        'telefono',
        'email',
        'direccion',
        'tipo_cliente',
        'estado',
        'localidad',
        'zona_id',
        'condicion_iva',
        'saldo',
    ];

    protected function casts(): array
    {
        return [
            'tipo_cliente' => ClientType::class,
            'estado' => ClientStatus::class,
            'saldo' => 'decimal:2',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function reclamos(): HasMany
    {
        return $this->hasMany(Reclamo::class, 'cliente_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class, 'cliente_id');
    }
}
