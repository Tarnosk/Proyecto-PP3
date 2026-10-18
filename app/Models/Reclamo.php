<?php

namespace App\Models;

use App\Enums\ClaimPriority;
use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclamo extends Model
{
    protected $table = 'reclamos';

    protected $fillable = [
        'cliente_id',
        'asunto',
        'descripcion',
        'prioridad',
    ];

    protected function casts(): array
    {
        return [
            'prioridad' => ClaimPriority::class,
            'estado' => ClaimStatus::class,
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
}
