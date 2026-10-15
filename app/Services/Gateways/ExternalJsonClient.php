<?php

namespace App\Services\Gateways;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ExternalJsonClient
{
    public function get(string $service, string $path, array $query = []): array
    {
        $url = config("services.{$service}.url");
        $token = config("services.{$service}.token");

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL) || ! is_string($token) || $token === '') {
            abort(503, 'El servicio externo no esta configurado.');
        }

        try {
            $response = Http::baseUrl(rtrim($url, '/'))
                ->withToken($token)
                ->acceptJson()
                ->connectTimeout(2)
                ->timeout(5)
                ->get($path, $query);
        } catch (ConnectionException) {
            abort(503, 'El servicio externo no esta disponible.');
        }

        if (! $response->successful()) {
            abort(502, 'El servicio externo respondio con un error.');
        }

        $body = $response->json();
        if (! is_array($body) || Validator::make($body, ['data' => ['present', 'array']])->fails()) {
            abort(502, 'El servicio externo devolvio datos invalidos.');
        }

        return $body['data'];
    }
}
