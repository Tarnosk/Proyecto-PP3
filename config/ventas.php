<?php

/**
 * CONFIGURACIÓN DEL MÓDULO VENTAS
 *
 * Se lee en el código con config('ventas.xxx').
 * Los valores con env(...) se pueden cambiar desde el archivo .env sin tocar código.
 */
return [

    /*
    | ¿De dónde salen productos, clientes y stock?
    |   "mock" → datos de prueba (App\Services\Mock*). ES EL VALOR POR DEFECTO.
    |   "http" → APIs reales de los otros grupos (App\Modules\Ventas\Integraciones\Http*).
    | El cambio se aplica en app/Providers/AppServiceProvider.php.
    */
    'integracion' => env('VENTAS_INTEGRACION', 'mock'),

    /*
    | Direcciones de las APIs de los otros módulos (solo se usan con "http").
    */
    'servicios' => [
        'productos' => ['url' => env('VENTAS_PRODUCTOS_URL', 'http://localhost:8001')],
        'clientes' => ['url' => env('VENTAS_CLIENTES_URL', 'http://localhost:8002')],
        'stock' => ['url' => env('VENTAS_STOCK_URL', 'http://localhost:8003')],
    ],

    /*
    | Segundos máximos de espera a otro módulo antes de responder 503.
    */
    'timeout' => (int) env('VENTAS_HTTP_TIMEOUT', 5),

    /*
    | Paginación de GET /api/ventas.
    */
    'por_pagina' => 15,
    'por_pagina_max' => 100,

    /*
    | Texto que se guarda cuando la venta no tiene observaciones
    | (el mismo que usa VentaController@store).
    */
    'observaciones_por_defecto' => 'Venta POS a cuenta corriente',
];
