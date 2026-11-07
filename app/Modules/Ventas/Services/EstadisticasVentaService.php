<?php

namespace App\Modules\Ventas\Services;

use App\Modules\Ventas\Contracts\ClienteGateway;
use App\Modules\Ventas\Contracts\ProductoGateway;
use App\Modules\Ventas\Enums\EstadoVenta;
use App\Modules\Ventas\Models\Venta;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * ESTADÍSTICAS DE VENTAS POR LOCALIDAD
 *
 * La venta no guarda la localidad: la tiene el cliente (texto libre en el
 * módulo Clientes, sin id). Por eso se piden los clientes UNA vez, se agrupan
 * por localidad y se suman sus ventas.
 *
 * Reglas:
 *  - Las ventas anuladas (cancelada) no suman en cantidades ni totales;
 *    sí aparecen en "ventas_por_estado".
 *  - El nombre se compara sin mayúsculas ni tildes: "pigue" = "Pigüé".
 *  - desde/hasta son opcionales.
 *
 * Solo lee: no cambia nada.
 */
class EstadisticasVentaService
{
    public function __construct(
        private readonly ClienteGateway $clientes,
        private readonly ProductoGateway $productos,
    ) {}

    /**
     * Resumen de todas las localidades, de mayor a menor total vendido.
     */
    public function localidades(?string $desde, ?string $hasta): array
    {
        $grupos = $this->clientesPorLocalidad();
        $ventas = $this->ventas($grupos->flatten(1)->pluck('id')->all(), $desde, $hasta);

        return $grupos
            ->map(function (Collection $clientes) use ($ventas) {
                $validas = $this->validas($ventas->whereIn('id_cliente', $clientes->pluck('id')));

                return ['localidad' => $clientes->first()['localidad']] + $this->resumen($validas);
            })
            ->sortByDesc('total_vendido')
            ->values()
            ->all();
    }

    /**
     * Detalle de una localidad. null si no tiene clientes.
     */
    public function localidad(string $nombre, ?string $desde, ?string $hasta): ?array
    {
        $clientes = $this->clientesPorLocalidad()->get($this->clave($nombre));

        if ($clientes === null) {
            return null;
        }

        $ventas = $this->ventas($clientes->pluck('id')->all(), $desde, $hasta);
        $validas = $this->validas($ventas);

        return [
            'localidad' => $clientes->first()['localidad'],
            'desde' => $desde,
            'hasta' => $hasta,
            ...$this->resumen($validas),
            'ventas_por_estado' => $ventas->countBy('estado')->all(),
            'clientes' => $clientes
                ->map(function (array $cliente) use ($validas) {
                    $suyas = $validas->where('id_cliente', $cliente['id']);

                    return [
                        'id' => $cliente['id'],
                        'nombre' => $cliente['nombre'],
                        'cantidad_ventas' => $suyas->count(),
                        'total' => round((float) $suyas->sum('total'), 2),
                    ];
                })
                ->sortByDesc('total')
                ->values()
                ->all(),
            'productos_mas_vendidos' => $this->productosMasVendidos($validas),
        ];
    }

    /**
     * Clientes agrupados por localidad normalizada (sin tildes ni mayúsculas).
     */
    private function clientesPorLocalidad(): Collection
    {
        return collect($this->clientes->todos())
            ->filter(fn (array $cliente) => trim($cliente['localidad'] ?? '') !== '')
            ->groupBy(fn (array $cliente) => $this->clave($cliente['localidad']));
    }

    private function ventas(array $idsClientes, ?string $desde, ?string $hasta): Collection
    {
        return Venta::query()
            ->with('detalles')
            ->whereIn('id_cliente', $idsClientes)
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
            ->get();
    }

    private function validas(Collection $ventas): Collection
    {
        return $ventas->reject(fn (Venta $venta) => EstadoVenta::desdeTexto($venta->estado) === EstadoVenta::Cancelada);
    }

    private function resumen(Collection $ventas): array
    {
        $cantidad = $ventas->count();
        $total = round((float) $ventas->sum('total'), 2);

        return [
            'cantidad_ventas' => $cantidad,
            'total_vendido' => $total,
            'ticket_promedio' => $cantidad > 0 ? round($total / $cantidad, 2) : 0,
            'clientes_con_compras' => $ventas->pluck('id_cliente')->unique()->count(),
        ];
    }

    /**
     * Los 10 productos con más unidades vendidas.
     */
    private function productosMasVendidos(Collection $ventas): array
    {
        $catalogo = collect($this->productos->todos())->keyBy('id');

        return $ventas->flatMap(fn (Venta $venta) => $venta->detalles)
            ->groupBy('id_producto')
            ->map(fn (Collection $lineas, $id) => [
                'id_producto' => (int) $id,
                'producto' => $catalogo[$id]['nombre'] ?? "Producto #{$id}",
                'unidades' => (int) $lineas->sum('cantidad'),
                'total' => round((float) $lineas->sum('subtotal'), 2),
            ])
            ->sortByDesc('unidades')
            ->take(10)
            ->values()
            ->all();
    }

    private function clave(string $localidad): string
    {
        return Str::lower(Str::ascii(trim($localidad)));
    }
}
