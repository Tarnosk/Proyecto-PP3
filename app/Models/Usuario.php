<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'usuario',
        'nombre',
        'rol',
        'estado',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
    ];
}
