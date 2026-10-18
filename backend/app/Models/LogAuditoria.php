<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAuditoria extends Model
{
    protected $table = 'log_auditoria';

    protected $primaryKey = 'id_auditoria';

    public $timestamps = false;

    protected $fillable = [
        'tabla_afectada',
        'id_registro',
        'accion',
        'valores_anteriores',
        'valores_nuevos',
        'id_usuario',
        'fecha_hora',
    ];

    protected $casts = [
        'id_auditoria' => 'integer',
        'id_registro' => 'integer',
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'id_usuario' => 'integer',
        'fecha_hora' => 'datetime',
    ];

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
