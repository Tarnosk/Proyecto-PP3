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
        $url = config("services.{$service}.url");
        $token = config("services.{$service}.token");

        if (! $this->isConfigured($service)) {
            abort(503, 'El servicio externo no esta configurado.');
        }

        try {
            $request = Http::baseUrl(rtrim($url, '/'))
                ->acceptJson()
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
        if (! is_array($body) || Validator::make($body, ['data' => ['present', 'array']])->fails()) {
            abort(502, 'El servicio externo devolvio datos invalidos.');
        }

        return $body['data'];
    }
}
