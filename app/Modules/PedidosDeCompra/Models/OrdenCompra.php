<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Models;

use App\Modules\Proveedores\Models\Proveedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    protected $table = 'ORDEN_COMPRA';
    protected $primaryKey = 'id_orden';
    public $timestamps = false;

    protected $fillable = [
        'numero_orden',
        'id_proveedor',
        'total_estimado',
        'estado',
        'cierre_parcial',
        'observaciones',
        'fecha_creacion',
        'fecha_modificacion',
        'fecha_envio',
        'fecha_entrega_estimada',
        'fecha_cancelacion',
        'id_usuario',
    ];

    protected $casts = [
        'id_orden' => 'integer',
        'cierre_parcial' => 'boolean',
        'total_estimado' => 'float',
        'fecha_creacion' => 'date:Y-m-d',
        'fecha_modificacion' => 'date:Y-m-d',
        'fecha_envio' => 'date:Y-m-d',
        'fecha_entrega_estimada' => 'date:Y-m-d',
        'fecha_cancelacion' => 'date:Y-m-d',
    ];

    /**
     * Estados válidos de una orden de compra (CHECK de la tabla ORDEN_COMPRA).
     */
    public const ESTADOS = ['pendiente', 'enviada', 'recibida_parcialmente', 'completada', 'cancelada'];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleOrden::class, 'id_orden', 'id_orden');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(\App\Modules\Compras\Models\Compra::class, 'id_orden', 'id_orden');
    }

    /**
     * Recalcula y sincroniza el estado logístico de la Orden de Compra según
     * las cantidades efectivamente recibidas en todas sus compras activas (no canceladas).
     */
    public function sincronizarEstadoRecepcion(): void
    {
        if ($this->estado === 'cancelada') {
            return;
        }

        $this->load(['detalles', 'compras.detalles']);

        $comprasActivas = $this->compras->filter(fn ($c) => $c->estado !== 'cancelada');

        if ($comprasActivas->isEmpty()) {
            if (in_array($this->estado, ['recibida_parcialmente', 'completada'], true)) {
                $this->estado = $this->fecha_envio ? 'enviada' : 'pendiente';
                $this->fecha_modificacion = \Carbon\Carbon::now()->toDateString();
                $this->save();
            }
            return;
        }

        $recibidoPorProducto = [];
        $totalRecibidoGeneral = 0;

        foreach ($comprasActivas as $compra) {
            foreach ($compra->detalles as $detCompra) {
                $idProd = (int) $detCompra->id_producto;
                $rec = (int) $detCompra->cantidad_recibida;
                $recibidoPorProducto[$idProd] = ($recibidoPorProducto[$idProd] ?? 0) + $rec;
                $totalRecibidoGeneral += $rec;
            }
        }

        $todosCumplidos = $this->detalles->isNotEmpty();
        foreach ($this->detalles as $detOrden) {
            $idProd = (int) $detOrden->id_producto;
            $solicitado = (int) $detOrden->cantidad_solicitada;
            $recibido = $recibidoPorProducto[$idProd] ?? 0;
            if ($recibido < $solicitado) {
                $todosCumplidos = false;
                break;
            }
        }

        if ($todosCumplidos && $totalRecibidoGeneral > 0) {
            $nuevoEstado = 'completada';
        } elseif ($this->cierre_parcial && $totalRecibidoGeneral > 0) {
            $nuevoEstado = 'completada';
        } elseif ($totalRecibidoGeneral > 0) {
            $nuevoEstado = 'recibida_parcialmente';
        } else {
            $nuevoEstado = $this->fecha_envio ? 'enviada' : 'pendiente';
        }

        $this->estado = $nuevoEstado;
        if (!$this->fecha_envio) {
            $this->fecha_envio = \Carbon\Carbon::now()->toDateString();
        }
        $this->fecha_modificacion = \Carbon\Carbon::now()->toDateString();
        $this->save();
    }
}
