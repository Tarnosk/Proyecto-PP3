<?php

namespace App\Services\Gateways;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ExternalJsonClient
{
    public function isConfigured(string $service): bool
    {
        return filter_var(config("services.{$service}.url"), FILTER_VALIDATE_URL) !== false
            && (! config("services.{$service}.auth_required", true)
                || (is_string(config("services.{$service}.token")) && config("services.{$service}.token") !== ''));
    }

    public function get(string $service, string $path, array $query = []): array
    {
        return $this->body($service, $path, $query)['data'];
    }

    /** Lee todas las páginas sin seguir enlaces devueltos por otro servicio. */
    public function getList(string $service, string $path, array $query = []): array
    {
        $rows = [];
        $lastPage = null;
        $total = null;
        for ($page = 1; $page <= 100; $page++) {
            $body = $this->body($service, $path, [...$query, 'page' => $page]);
            abort_unless(array_is_list($body['data']), 502, 'El servicio externo devolvio una lista invalida.');
            $rows = array_merge($rows, $body['data']);

            if (! array_key_exists('meta', $body)) {
                abort_unless($page === 1, 502, 'El servicio externo interrumpio la paginacion.');

                return $rows;
            }
            $meta = $body['meta'];
            abort_unless(is_array($meta) && ! Validator::make($meta, [
                'current_page' => ['required', 'integer', 'min:1'],
                'last_page' => ['required', 'integer', 'min:1', 'max:100'],
                'total' => ['sometimes', 'integer', 'min:0'],
            ])->fails(), 502, 'El servicio externo devolvio paginacion invalida.');
            abort_unless((int) $meta['current_page'] === $page && (int) $meta['last_page'] >= $page,
                502, 'El servicio externo devolvio otra pagina.');
            $lastPage ??= (int) $meta['last_page'];
            $total ??= isset($meta['total']) ? (int) $meta['total'] : null;
            abort_unless($lastPage === (int) $meta['last_page'] && (! isset($meta['total']) || $total === (int) $meta['total']),
                502, 'El servicio externo cambio la paginacion durante la consulta.');
            if ($page === $lastPage) {
                abort_unless($total === null || count($rows) === $total, 502, 'El servicio externo devolvio un listado incompleto.');

                return $rows;
            }
            abort_if($body['data'] === [], 502, 'El servicio externo devolvio un listado incompleto.');
        }

        abort(502, 'El listado externo supera el limite de paginas permitido.');
    }

    private function body(string $service, string $path, array $query): array
    {
        $url = config("services.{$service}.url");
        $token = config("services.{$service}.token");

        if (! $this->isConfigured($service)) {
            abort(503, 'El servicio externo no esta configurado.');
        }

        // Las rutas son relativas a la base configurada; no aceptar otra URL.
        abort_unless(str_starts_with($path, '/') && ! str_starts_with($path, '//') && ! str_contains($path, '://'),
            503, 'La ruta del servicio externo no es valida.');

        try {
            $request = Http::baseUrl(rtrim($url, '/'))
                ->acceptJson()
                ->withoutRedirecting()
                ->connectTimeout(2)
                ->timeout(5);
            if (is_string($token) && $token !== '') {
                $request = $request->withToken($token);
            }
            $response = $request->get($path, $query);
        } catch (ConnectionException) {
            abort(503, 'El servicio externo no esta disponible.');
        }

        if (! $response->successful()) {
            abort(502, 'El servicio externo respondio con un error.');
        }

        $body = $response->json();
        if (! is_array($body) || ($body['error'] ?? false) || ($body['status'] ?? null) === 'error'
            || Validator::make($body, ['data' => ['present', 'array']])->fails()) {
            abort(502, 'El servicio externo devolvio datos invalidos.');
        }

        return $body;
    }
}
