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
            $clientes = [];
            $page = 1;

            do {
                $respuesta = $this->cliente()
                    ->get('/api/v1/clientes', ['estado' => 'activo', 'per_page' => 100, 'page' => $page])
                    ->throw()
                    ->json();

                $clientes = array_merge($clientes, $respuesta['data'] ?? []);
                $lastPage = (int) ($respuesta['meta']['last_page'] ?? 1);
                $page++;
            } while ($page <= $lastPage);
        } catch (Throwable $e) {
            throw new IntegracionNoDisponibleException('clientes', $e);
        }

        return $this->cache = array_values(array_map(
            fn (array $cliente) => $this->normalizar($cliente),
            $clientes
        ));
    }

    public function buscar(int $id): ?array
    {
        try {
            $respuesta = $this->cliente()->get("/api/v1/clientes/{$id}");
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

    private function normalizar(array $cliente): array
    {
        return [
            'id' => (int) ($cliente['id'] ?? 0),
            'nombre' => trim((string) (($cliente['nombre'] ?? '').' '.($cliente['apellido_razon_social'] ?? ''))),
            'cuit' => (string) ($cliente['dni_cuit'] ?? ''),
            'condicion' => (string) ($cliente['condicion_iva'] ?? ''),
            'domicilio' => (string) ($cliente['direccion'] ?? ''),
            'localidad' => (string) ($cliente['localidad'] ?? ''),
            'movimientos' => [],
        ];
    }

    private function cliente(): PendingRequest
    {
        $cliente = Http::baseUrl(rtrim((string) config('ventas.servicios.clientes.url'), '/'))
            ->timeout((int) config('ventas.timeout'))
            ->acceptJson();

        $token = config('services.internal_api.token');

        return is_string($token) && $token !== '' ? $cliente->withToken($token) : $cliente;
    }
}
