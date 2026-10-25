<?php

namespace App\Modules\Ventas\Integraciones;

use App\Modules\Ventas\Contracts\ProductoGateway;
use App\Modules\Ventas\Exceptions\IntegracionNoDisponibleException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * PRODUCTOS DESDE LA API REAL (APAGADA HOY - lista para integrar)
 *
 * "Código real" para cuando el módulo de Productos publique su API.
 * Está escrito y testeado (tests/Unit/Integraciones/HttpProductoApiTest.php)
 * pero NO se usa mientras VENTAS_INTEGRACION=mock.
 *
 * Para activarla, en el .env:
 *   VENTAS_INTEGRACION=http
 *   VENTAS_PRODUCTOS_URL=http://url-del-modulo-productos
 *
 * ⚠ CONTRATO SUPUESTO: todavía no sabemos los nombres exactos de las rutas
 * ni de los campos que va a usar el otro grupo. Se asumió:
 *   GET {url}/api/productos       → lista de productos
 *   GET {url}/api/productos/{id}  → un producto (404 si no existe)
 * y normalizar() acepta varios nombres posibles de campos. Cuando el otro
 * grupo confirme su formato, se ajusta SOLO este archivo.
 */
class HttpProductoApi implements ProductoGateway
{
    /**
     * Memoria del pedido actual: si en la misma operación se piden los
     * productos dos veces, se llama a la API una sola vez.
     */
    private ?array $cache = null;

    public function todos(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        try {
            $respuesta = $this->cliente()->get('/api/productos')->throw()->json();
        } catch (Throwable $e) {
            // Timeout, servidor caído, error 500... → 503 para nuestro front.
            throw new IntegracionNoDisponibleException('productos', $e);
        }

        // Algunas APIs devuelven la lista directo y otras dentro de "data".
        $lista = $respuesta['data'] ?? $respuesta;

        return $this->cache = array_values(array_map(fn (array $p) => $this->normalizar($p), $lista));
    }

    public function buscar(int $id): ?array
    {
        try {
            $respuesta = $this->cliente()->get("/api/productos/{$id}");
        } catch (Throwable $e) {
            throw new IntegracionNoDisponibleException('productos', $e);
        }

        // 404 no es una falla: el producto simplemente no existe.
        if ($respuesta->notFound()) {
            return null;
        }

        if ($respuesta->failed()) {
            throw new IntegracionNoDisponibleException('productos', $respuesta->toException());
        }

        return $this->normalizar($respuesta->json('data') ?? $respuesta->json());
    }

    /**
     * Traduce el formato del otro módulo al formato que usa Ventas.
     */
    private function normalizar(array $p): array
    {
        $precioBase = $p['precio_unitario'] ?? 0;

        return [
            'id' => (int) ($p['id'] ?? $p['id_producto']),
            'codigo' => (string) ($p['codigo'] ?? ''),
            'nombre' => (string) ($p['nombre'] ?? $p['descripcion'] ?? ''),
            'precioMin' => (float) ($p['precioMin'] ?? $p['precio_minorista'] ?? $precioBase),
            'precioMay' => (float) ($p['precioMay'] ?? $p['precio_mayorista'] ?? $precioBase),
            'stock' => isset($p['stock']) ? (int) $p['stock'] : null,
        ];
    }

    /**
     * Cliente HTTP ya configurado: URL base, espera máxima y que pida JSON.
     */
    private function cliente(): PendingRequest
    {
        return Http::baseUrl((string) config('ventas.servicios.productos.url'))
            ->timeout((int) config('ventas.timeout'))
            ->acceptJson();
    }
}
