<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    public const UPDATED_AT = null;

    protected $fillable = [
        'usuario',
        'password_hash',
        'nombre',
        'email',
        'rol',
        'estado',
    ];

    protected $hidden = [
        'password_hash',
        'current_session_id',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function clientesCreados(): HasMany
    {
        return $this->hasMany(Cliente::class, 'creado_por');
    }

    public function reclamos(): HasMany
    {
        return $this->hasMany(Reclamo::class, 'usuario_id');
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class, 'usuario_id');
    }

    public function passwordResetCode(): HasOne
    {
        return $this->hasOne(PasswordResetCode::class, 'usuario_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class, 'usuario_id');
    }

    protected function casts(): array
    {
        return [
            'rol' => UserRole::class,
            'estado' => UserStatus::class,
            'bloqueado_hasta' => 'datetime',
            'ultimo_intento_fallido' => 'datetime',
            'intentos_fallidos' => 'integer',
        ];
    }
}
