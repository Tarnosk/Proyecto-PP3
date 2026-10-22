<?php
namespace App\Modules\Shared\Http;

/**
 * Helper para responder en formato JSON estándar:
 * { "data": ..., "error": bool, "mensaje": "...", "codigo": int }
 */
class Response
{
    public static function json($data, int $status = 200, ?string $mensaje = null): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'error' => false,
            'codigo' => $status,
            'mensaje' => $mensaje ?? 'Operación exitosa',
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function error(string $mensaje, int $status = 400, $detalles = null): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'error' => true,
            'codigo' => $status,
            'mensaje' => $mensaje,
            'detalles' => $detalles
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
