<?php

namespace App\Modules\Ventas\Integraciones;

use App\Modules\Ventas\Contracts\ClienteGateway;
use App\Modules\Ventas\Exceptions\IntegracionNoDisponibleException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * CLIENTES DESDE LA API REAL (APAGADA HOY - lista para integrar)
 *
 * Mismo esquema que HttpProductoApi. Testeada en
 * tests/Unit/Integraciones/HttpClienteApiTest.php.
 *
 * Para activarla, en el .env:
 *   VENTAS_INTEGRACION=http
 *   VENTAS_CLIENTES_URL=http://url-del-modulo-clientes
 *
 * ⚠ CONTRATO SUPUESTO (ajustar cuando el otro grupo lo confirme):
 *   GET {url}/api/clientes       → lista de clientes
 *   GET {url}/api/clientes/{id}  → un cliente (404 si no existe)
 */
class HttpClienteApi implements ClienteGateway
{
    private ?array $cache = null;

    public function todos(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        try {
            $respuesta = $this->cliente()->get('/api/clientes')->throw()->json();
        } catch (Throwable $e) {
            throw new IntegracionNoDisponibleException('clientes', $e);
        }

        $lista = $respuesta['data'] ?? $respuesta;

        return $this->cache = array_values(array_map(fn (array $c) => $this->normalizar($c), $lista));
    }

    public function buscar(int $id): ?array
    {
        try {
            $respuesta = $this->cliente()->get("/api/clientes/{$id}");
        } catch (Throwable $e) {
            throw new IntegracionNoDisponibleException('clientes', $e);
        }

        if ($respuesta->notFound()) {
            return null;
        }

        if ($respuesta->failed()) {
            throw new IntegracionNoDisponibleException('clientes', $respuesta->toException());
        }

        return $this->normalizar($respuesta->json('data') ?? $respuesta->json());
    }

    /**
     * Traduce el formato del otro módulo al formato que usa Ventas.
     */
    private function normalizar(array $c): array
    {
        return [
            'id' => (int) ($c['id'] ?? $c['id_cliente']),
            'nombre' => (string) ($c['nombre'] ?? $c['razon_social'] ?? ''),
            'cuit' => (string) ($c['cuit'] ?? ''),
        ];
    }

    private function cliente(): PendingRequest
    {
        return Http::baseUrl((string) config('ventas.servicios.clientes.url'))
            ->timeout((int) config('ventas.timeout'))
            ->acceptJson();
    }
}
