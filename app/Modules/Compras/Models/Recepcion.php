<?php
declare(strict_types=1);

namespace App\Modules\Compras\Models;

use App\Modules\Proveedores\Models\Proveedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recepcion extends Model
{
    protected $table = 'RECEPCION';
    protected $primaryKey = 'id_recepcion';
    public $timestamps = false;

    protected $fillable = [
        'id_compra',
        'id_proveedor',
        'fecha_recepcion',
        'id_usuario',
    ];

    protected $casts = [
        'id_recepcion' => 'integer',
        'fecha_recepcion' => 'date:Y-m-d',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleRecepcion::class, 'id_recepcion', 'id_recepcion');
    }
}
