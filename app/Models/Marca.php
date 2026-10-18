<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    protected $table = 'marcas';

    protected $fillable = ['nombre'];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'marca_id');
    }
}
