<?php

return [
    // URL acordada con G4, incluyendo su prefijo API. Sin fallback local.
    'ventas_url' => env('G2_VENTAS_API_URL'),
    'ventas_token' => env('G2_VENTAS_API_TOKEN'),
    'timeout' => 5,
];
