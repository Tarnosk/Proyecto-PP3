<?php

namespace App\Modules\Ventas\Models;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Login\Models\User;
use App\Modules\Ventas\Enums\PaymentMethod;
use App\Modules\Ventas\Enums\PaymentStatus;
use Database\Factories\CobroFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[UseFactory(CobroFactory::class)]
class Cobro extends Model
{
    /** @use HasFactory<CobroFactory> */
    use HasFactory;

    protected $table = 'cobros';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'monto_total' => 'decimal:2',
            'medio_pago' => PaymentMethod::class,
            'estado' => PaymentStatus::class,
            'anulado_en' => 'datetime',
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

    public function ventas(): BelongsToMany
    {
        return $this->belongsToMany(Venta::class, 'cobro_ventas', 'cobro_id', 'venta_id')
            ->withPivot('monto_aplicado');
    }
}
