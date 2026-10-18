<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zona extends Model
{
    public $timestamps = false;

    protected $table = 'zonas';

    protected $fillable = ['nombre', 'descripcion'];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'zona_id');
    }
}
