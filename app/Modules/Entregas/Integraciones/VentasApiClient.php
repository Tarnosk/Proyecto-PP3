<?php

namespace App\Modules\Entregas\Integraciones;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

/** Cliente de G2: sólo HTTP. No consulta ni modifica tablas de G3/G4. */
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

    private function http(): PendingRequest
    {
        $request = Http::baseUrl($this->getBaseUrl())->acceptJson()
            ->connectTimeout(config('g2.timeout'))->timeout(config('g2.timeout'));

        return config('g2.ventas_token') ? $request->withToken(config('g2.ventas_token')) : $request;
    }

    public function obtenerVentasPendientesDespacho(?int $idZona = null): array
    {
        $sales = [];
        $page = 1;
        do {
            $response = $this->http()->get('ventas', array_filter(['estado' => 'confirmada', 'id_zona' => $idZona, 'page' => $page]))->throw();
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

        if ($idZona !== null && count(array_filter($sales, fn ($sale) => ! isset($sale['id_zona']))) > 0) {
            throw new UnexpectedValueException('Ventas debe informar la zona para aplicar este filtro.');
        }

        return array_values(array_filter($sales,
            fn ($sale) => ($sale['estado'] ?? null) === 'confirmada'
                && ($idZona === null || (int) ($sale['id_zona'] ?? 0) === $idZona)));
    }

    public function obtenerVentaPorId(int $id): ?array
    {
        $response = $this->http()->get("ventas/{$id}");
        if ($response->status() === 404) {
            return null;
        }
        $body = $response->throw()->json();
        if (($body['error'] ?? false) || ! is_array($body['data'] ?? $body)) {
            throw new UnexpectedValueException('Ventas devolvió un detalle incompatible con el contrato de G2.');
        }
        $sale = $this->normalizar($body['data'] ?? $body);
        if ($sale['id_venta'] !== $id) {
            throw new UnexpectedValueException('La respuesta de Ventas no corresponde al pedido solicitado.');
        }

        return $sale;
    }

    public function actualizarEstadoVenta(int $id, string $estado): bool
    {
        $response = $this->http()->patch("ventas/{$id}/estado", ['estado' => $estado])->throw();
        if ($response->json('error') === true) {
            throw new UnexpectedValueException('Ventas no aceptó la actualización del estado.');
        }

        return true;
    }

    private function normalizar(array $sale): array
    {
        $sale['id_venta'] = (int) ($sale['id_venta'] ?? $sale['id'] ?? 0);
        $sale['id_cliente'] = (int) ($sale['id_cliente'] ?? $sale['cliente']['id'] ?? 0);
        // Equivalencias documentadas por VentaResource de G4; no inferir pagos ni estados nuevos.
        $sale['estado'] = is_array($sale['estado'] ?? null) ? ($sale['estado']['valor'] ?? null) : ($sale['estado'] ?? null);
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
