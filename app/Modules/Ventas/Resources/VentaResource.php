<?php

namespace App\Modules\Ventas\Resources;

use App\Modules\Ventas\Contracts\ClienteGateway;
use App\Modules\Ventas\Contracts\ProductoGateway;
use App\Modules\Ventas\Enums\EstadoVenta;
use App\Modules\Ventas\Models\VentaHistorial;
use App\Services\MockUsuarioApi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * FORMATO JSON DE UNA VENTA (lo que recibe el front)
 *
 * Un "Resource" decide cómo se ve un modelo cuando se convierte en JSON.
 * Así TODAS las rutas de la API devuelven una venta con la misma forma, y
 * si mañana cambia algo, se cambia solo acá.
 *
 * Ejemplo:
 * {
 *   "id": 5,
 *   "numero_comprobante": "POS-01M4VQNAJ6N36B4A...",
 *   "fecha": "2026-10-13",
 *   "cliente": { "id": 472891, "nombre": "Supermercado El Norte SRL", "cuit": "30-71234567-8" },
 *   "usuario": { "id": 1, "nombre": "Administrador" },
 *   "estado": { "valor": "confirmada", "etiqueta": "Confirmada" },
 *   "anulada": false,
 *   "total": 159960, "descuento_global": 0, "observaciones": "anotacion aaaa",
 *   "cantidad_articulos": 118,
 *   "detalles": [ { "id_producto": 2, "codigo": "P002", "producto": "Pan lactal grande", "cantidad": 3, ... } ],
 *   "acciones": { "puede_modificar": true, "puede_eliminar": true, "puede_restaurar": false },
 *   "transiciones_posibles": ["preparada", "entregada", "pagada"],
 *   "historial": [ ... ]   ← solo en GET /api/ventas/{id} y en las respuestas de cambios
 * }
 *
 * Clientes y productos salen de ClienteGateway / ProductoGateway
 * (hoy mocks; mañana las APIs reales, sin tocar este archivo).
 */
class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cliente = app(ClienteGateway::class)->buscar((int) $this->id_cliente);
        $usuarios = app(MockUsuarioApi::class);
        $estado = EstadoVenta::desdeTexto($this->estado);
        $acciones = EstadoVenta::accionesPara($this->estado);

        return [
            'id' => (int) $this->id_venta,
            'numero_comprobante' => $this->numFactura,
            'fecha' => substr((string) $this->fecha, 0, 10),
            'cliente' => [
                'id' => (int) $this->id_cliente,
                'nombre' => $cliente['nombre'] ?? "Cliente #{$this->id_cliente}",
                'cuit' => $cliente['cuit'] ?? null,
            ],
            'usuario' => [
                'id' => (int) $this->id_usuario,
                'nombre' => $usuarios->nombre((int) $this->id_usuario),
            ],
            'estado' => [
                'valor' => $this->estado,
                'etiqueta' => $estado?->etiqueta() ?? 'Desconocido',
            ],
            'anulada' => $acciones['anulada'],
            'total' => (float) $this->total,
            'descuento_global' => (float) ($this->descuento_global ?? 0),
            'observaciones' => $this->observaciones,
            'cantidad_articulos' => (int) $this->detalles->sum('cantidad'),
            'detalles' => $this->detalles->map(fn ($d) => $this->detalle($d))->values()->all(),
            'acciones' => [
                'puede_modificar' => $acciones['puede_modificar'],
                'puede_eliminar' => $acciones['puede_eliminar'],
                'puede_restaurar' => $acciones['puede_restaurar'],
            ],
            'transiciones_posibles' => array_map(fn (EstadoVenta $e) => $e->value, $estado?->siguientes() ?? []),
            // whenLoaded: el historial solo aparece si se cargó (en el listado no, para que sea liviano).
            'historial' => $this->whenLoaded('historial', fn () => $this->historial->map(fn (VentaHistorial $h) => [
                'accion' => $h->accion,
                'estado_anterior' => $h->estado_anterior,
                'estado_nuevo' => $h->estado_nuevo,
                'motivo' => $h->motivo,
                'datos' => $h->datos,
                'usuario' => $h->id_usuario ? $usuarios->nombre((int) $h->id_usuario) : null,
                'fecha' => $h->created_at?->format('Y-m-d H:i:s'),
            ])->values()->all()),
        ];
    }

    /**
     * Una línea del detalle con el nombre y código del producto.
     */
    private function detalle($d): array
    {
        $producto = app(ProductoGateway::class)->buscar((int) $d->id_producto);

        return [
            'id_producto' => (int) $d->id_producto,
            'codigo' => $producto['codigo'] ?? null,
            'producto' => $producto['nombre'] ?? "Producto #{$d->id_producto}",
            'cantidad' => (int) $d->cantidad,
            'precio_unitario' => (float) $d->precio_unitario,
            'descuento' => (float) ($d->descuento ?? 0),
            'subtotal' => (float) $d->subtotal,
        ];
    }
}
