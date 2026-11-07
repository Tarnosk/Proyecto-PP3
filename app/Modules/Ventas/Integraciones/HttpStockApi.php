<?php

namespace App\Modules\Ventas\Integraciones;

use App\Modules\Ventas\Contracts\StockGateway;
use App\Modules\Ventas\Exceptions\IntegracionNoDisponibleException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Stock desde la API real de Productos/Stock. */
class HttpStockApi implements StockGateway
{
    public function ajustar(array $cambios, int $ventaId, string $motivo): void
    {
        // Se descartan los productos sin cambios (cantidad 0): no hay nada que avisar.
        $items = [];
        foreach ($cambios as $idProducto => $cantidad) {
            if ($cantidad !== 0) {
                $items[] = ['id_producto' => (int) $idProducto, 'cantidad' => (int) $cantidad];
            }
        }

        if ($items === []) {
            return;
        }

        try {
            $this->cliente()->post('/api/v1/stock/movimientos', [
                'origen' => 'ventas',
                'venta_id' => $ventaId,
                'motivo' => $motivo,
                'items' => $items,
            ])->throw();
        } catch (Throwable $e) {
            throw new IntegracionNoDisponibleException('stock', $e);
        }
    }

    public function disponible(int $idProducto): ?int
    {
        try {
            $respuesta = $this->cliente()->get("/api/v1/stock/{$idProducto}");
        } catch (Throwable $e) {
            throw new IntegracionNoDisponibleException('stock', $e);
        }

        if ($respuesta->notFound()) {
            return null;
        }

        if ($respuesta->failed()) {
            throw new IntegracionNoDisponibleException('stock', $respuesta->toException());
        }

        $disponible = $respuesta->json('disponible');
        if ($disponible === null) {
            $disponible = $respuesta->json('cantidad_disponible');
        }
        if ($disponible === null) {
            $disponible = $respuesta->json('stock_disponible');
        }
        if ($disponible === null) {
            $disponible = $respuesta->json('stock');
        }

        return $disponible === null ? null : (int) $disponible;
    }

    private function cliente(): PendingRequest
    {
        $cliente = Http::baseUrl(rtrim((string) config('ventas.servicios.stock.url'), '/'))
            ->timeout((int) config('ventas.timeout'))
            ->acceptJson();

        $token = config('services.internal_api.token');

        return is_string($token) && $token !== '' ? $cliente->withToken($token) : $cliente;
    }
}
