<?php

namespace App\Modules\Ventas\Integraciones;

use App\Modules\Ventas\Contracts\StockGateway;
use App\Modules\Ventas\Exceptions\IntegracionNoDisponibleException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * STOCK DESDE LA API REAL (APAGADA HOY - lista para integrar)
 *
 * Testeada en tests/Unit/Integraciones/HttpStockApiTest.php.
 *
 * Para activarla, en el .env:
 *   VENTAS_INTEGRACION=http
 *   VENTAS_STOCK_URL=http://url-del-modulo-stock
 *
 * ⚠ CONTRATO SUPUESTO (ajustar cuando el grupo de Stock lo confirme):
 *   POST {url}/api/stock/movimientos
 *        { "origen": "ventas", "venta_id": 5, "motivo": "anulacion",
 *          "items": [ { "id_producto": 1, "cantidad": 2 } ] }      ← cantidad con signo
 *   GET  {url}/api/stock/{id_producto}  → { "disponible": 48 }
 */
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
            $this->cliente()->post('/api/stock/movimientos', [
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
            $respuesta = $this->cliente()->get("/api/stock/{$idProducto}");
        } catch (Throwable $e) {
            throw new IntegracionNoDisponibleException('stock', $e);
        }

        if ($respuesta->notFound()) {
            return null;
        }

        if ($respuesta->failed()) {
            throw new IntegracionNoDisponibleException('stock', $respuesta->toException());
        }

        return (int) $respuesta->json('disponible');
    }

    private function cliente(): PendingRequest
    {
        return Http::baseUrl((string) config('ventas.servicios.stock.url'))
            ->timeout((int) config('ventas.timeout'))
            ->acceptJson();
    }
}
