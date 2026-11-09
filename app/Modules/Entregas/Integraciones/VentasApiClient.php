<?php

namespace App\Modules\Entregas\Integraciones;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

/**
 * Cliente HTTP del módulo G2 para consumir la API de Ventas (G4).
 * Diseñado bajo principios de resiliencia y desacoplamiento:
 * - Solo peticiones HTTP (sin acceso a tablas de otros grupos).
 * - Timeouts breves y política de reintentos ante fallas efímeras.
 * - Graceful degradation: captura errores de conexión y 5xx registrando logs.
 */
class VentasApiClient
{
    public function getBaseUrl(): string
    {
        $url = rtrim((string) config('g2.ventas_url'), '/');
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new UnexpectedValueException('La conexión de G2 con Ventas todavía no está configurada.');
        }

        return $url;
    }

    public function isConfigured(): bool
    {
        try {
            return $this->getBaseUrl() !== '';
        } catch (Throwable) {
            return false;
        }
    }

    private function http(): PendingRequest
    {
        $timeout = (int) (config('g2.timeout') ?: 3);

        $request = Http::baseUrl($this->getBaseUrl())
            ->acceptJson()
            ->connectTimeout($timeout)
            ->timeout($timeout)
            ->retry(2, 100, throw: false);

        return config('g2.ventas_token') ? $request->withToken(config('g2.ventas_token')) : $request;
    }

    /**
     * Obtiene ventas con estado confirmada listas para ser despachadas.
     * Retorna array vacío o lanza excepción si la URL no está configurada según contrato.
     */
    public function obtenerVentasPendientesDespacho(?int $idZona = null): array
    {
        $sales = [];
        $page = 1;

        try {
            do {
                $response = $this->http()->get('ventas', array_filter(['estado' => 'pendiente', 'id_zona' => $idZona, 'page' => $page]));

                if ($response->failed()) {
                    Log::warning("G2 - VentasApiClient: Error al consultar ventas pendientes (HTTP {$response->status()}).", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return [];
                }

                $body = $response->json();
                $rows = $body['data'] ?? $body;

                if (! is_array($rows) || ! array_is_list($rows) || ($body['error'] ?? false)) {
                    throw new UnexpectedValueException('Ventas devolvió un listado incompatible con el contrato de G2.');
                }

                $sales = array_merge($sales, array_map($this->normalizar(...), $rows));
                $lastPage = (int) ($body['meta']['last_page'] ?? 1);
                if ($lastPage < $page || $lastPage > 100) {
                    throw new UnexpectedValueException('La paginación de Ventas no puede procesarse de forma completa.');
                }
                $page++;
            } while ($page <= $lastPage);
        } catch (UnexpectedValueException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('G2 - VentasApiClient: Excepción al comunicarse con Ventas (G4): ' . $e->getMessage());
            return [];
        }

        if ($idZona !== null && count(array_filter($sales, fn ($sale) => ! isset($sale['id_zona']))) > 0) {
            throw new UnexpectedValueException('Ventas debe informar la zona para aplicar este filtro.');
        }

        return array_values(array_filter($sales,
            fn ($sale) => ($sale['estado'] ?? null) === 'pendiente'
                && ($idZona === null || (int) ($sale['id_zona'] ?? 0) === $idZona)));
    }

    /**
     * Obtiene el detalle de una venta por ID.
     * Retorna null si no existe, si falla la conexión o si el servicio externo está caído.
     */
    public function obtenerVentaPorId(int $id): ?array
    {
        if (! $this->isConfigured()) {
            Log::warning("G2 - VentasApiClient: No se puede obtener venta #{$id} porque la conexión no está configurada.");
            return null;
        }

        try {
            $response = $this->http()->get("ventas/{$id}");

            if ($response->status() === 404) {
                return null;
            }

            if ($response->failed()) {
                Log::warning("G2 - VentasApiClient: Error HTTP {$response->status()} al consultar venta #{$id}.", [
                    'body' => $response->body(),
                ]);
                return null;
            }

            $body = $response->json();
            if (($body['error'] ?? false) || ! is_array($body['data'] ?? $body)) {
                Log::warning("G2 - VentasApiClient: Venta #{$id} devuelta con estructura incompatible.");
                return null;
            }

            $sale = $this->normalizar($body['data'] ?? $body);
            if ($sale['id_venta'] !== $id) {
                Log::warning("G2 - VentasApiClient: El ID devuelto ({$sale['id_venta']}) no coincide con el solicitado ({$id}).");
                return null;
            }

            return $sale;
        } catch (Throwable $e) {
            Log::warning("G2 - VentasApiClient: Excepción al obtener venta #{$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Notifica a la API de Ventas (G4) el cambio de estado de una venta (ej. 'pagada', 'entregada').
     * Retorna false si falla sin lanzar excepciones que interrumpan los flujos de G2.
     */
    public function actualizarEstadoVenta(int $id, string $estado): bool
    {
        if (! $this->isConfigured()) {
            Log::warning("G2 - VentasApiClient: No se pudo actualizar estado de venta #{$id} a '{$estado}': conexión no configurada.");
            return false;
        }

        try {
            $response = $this->http()->patch("ventas/{$id}/estado", ['estado' => $estado]);

            if ($response->failed() || $response->json('error') === true) {
                Log::warning("G2 - VentasApiClient: Ventas no aceptó actualizar venta #{$id} a '{$estado}' (HTTP {$response->status()}).", [
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning("G2 - VentasApiClient: Excepción al actualizar estado de venta #{$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normaliza la estructura devuelta por G4 (VentaResource o array plano).
     */
    private function normalizar(array $sale): array
    {
        $sale['id_venta'] = (int) ($sale['id_venta'] ?? $sale['id'] ?? 0);
        $sale['id_cliente'] = (int) ($sale['id_cliente'] ?? $sale['cliente']['id'] ?? 0);

        // Equivalencias documentadas por VentaResource de G4
        $estado = is_array($sale['estado'] ?? null) ? ($sale['estado']['valor'] ?? null) : ($sale['estado'] ?? null);
        $sale['estado'] = is_string($estado) ? strtolower(trim($estado)) : null;

        $sale['cliente_nombre'] = $sale['cliente_nombre'] ?? $sale['cliente']['nombre'] ?? null;
        $sale['items'] = $sale['items'] ?? array_map(fn ($item) => [
            ...$item, 'producto_nombre' => $item['producto'] ?? null,
        ], $sale['detalles'] ?? []);

        if ($sale['id_venta'] <= 0 || $sale['id_cliente'] <= 0 || ! is_numeric($sale['total'] ?? null)) {
            throw new UnexpectedValueException('Faltan identificadores o el total real en la respuesta de Ventas.');
        }

        return $sale;
    }
}
