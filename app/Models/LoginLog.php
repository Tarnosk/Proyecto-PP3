<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    protected $table = 'login_logs';

    public const CREATED_AT = 'fecha_hora';

    public const UPDATED_AT = null;

    protected $fillable = ['fecha_hora', 'ip'];

    protected function casts(): array
    {
        return ['fecha_hora' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
