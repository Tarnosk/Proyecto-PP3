<?php

namespace App\Modules\Clientes\Models;

use App\Modules\Clientes\Enums\ClientStatus;
use App\Modules\Clientes\Enums\ClientType;
use App\Modules\Clientes\Services\ClientFields;
use App\Modules\Login\Models\User;
use App\Modules\Reclamos\Models\Reclamo;
use App\Modules\Ventas\Models\Cobro;
use App\Modules\Ventas\Models\Venta;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(ClienteFactory::class)]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido_razon_social',
        'dni_cuit',
        'tipo_documento',
        'telefono',
        'email',
        'direccion',
        'direccion_calle',
        'direccion_numero',
        'tipo_cliente',
        'estado',
        'localidad',
        'zona_id',
        'condicion_iva',
        'saldo',
    ];

    protected static function booted(): void
    {
        static::saving(function (Cliente $client): void {
            if ($client->isDirty('dni_cuit')) {
                $client->dni_cuit = ClientFields::documentValue($client->dni_cuit);
                if (! $client->isDirty('tipo_documento') || ! $client->tipo_documento) {
                    $client->tipo_documento = ClientFields::documentType($client->dni_cuit);
                }
            }

            if ($client->isDirty('direccion_calle') || $client->isDirty('direccion_numero')) {
                if (filled($client->direccion_calle) && filled($client->direccion_numero)) {
                    $client->direccion = $client->direccion_calle.' '.$client->direccion_numero;
                }
            } elseif ($client->isDirty('direccion')) {
                $client->fill(ClientFields::addressParts($client->direccion));
            }
        });
    }

    public function getTipoDocumentoAttribute(?string $value): ?string
    {
        return $value ?: ClientFields::documentType($this->attributes['dni_cuit'] ?? null);
    }

    public function getDireccionCalleAttribute(?string $value): ?string
    {
        return $value ?? ClientFields::addressParts($this->attributes['direccion'] ?? null)['direccion_calle'];
    }

    public function getDireccionNumeroAttribute(?string $value): ?string
    {
        return $value ?? ClientFields::addressParts($this->attributes['direccion'] ?? null)['direccion_numero'];
    }

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
