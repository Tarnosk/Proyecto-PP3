<?php

namespace App\Modules\Shared\Http;

use Illuminate\Http\JsonResponse;

/** Conserva el contrato de G2 y permite terminar el ciclo HTTP de Laravel. */
class Response
{
    public static function json($data, int $status = 200, ?string $mensaje = null): JsonResponse
    {
        return response()->json([
            'error' => false, 'codigo' => $status,
            'mensaje' => $mensaje ?? 'Operación exitosa', 'data' => $data,
        ], $status);
    }

    public static function error(string $mensaje, int $status = 400, $detalles = null): JsonResponse
    {
        return response()->json([
            'error' => true, 'codigo' => $status,
            'mensaje' => $mensaje, 'detalles' => $detalles,
        ], $status);
    }
}
