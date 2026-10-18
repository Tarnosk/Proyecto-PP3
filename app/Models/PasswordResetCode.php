<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetCode extends Model
{
    protected $fillable = [
        'usuario_id',
        'code_hash',
        'intentos',
        'expires_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'intentos' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
