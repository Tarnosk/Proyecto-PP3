<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'internal_api' => [
        'token' => env('INTERNAL_API_TOKEN'),
    ],

    'sales' => [
        'url' => env('SALES_SERVICE_URL'),
        'token' => env('SALES_SERVICE_TOKEN'),
        'auth_required' => env('SALES_AUTH_REQUIRED', false),
        'history_path' => env('SALES_HISTORY_PATH', '/api/ventas'),
        // Opcionales: si estan vacios, G1 deriva compradores y ranking de history_path.
        'buyers_path' => env('SALES_BUYERS_PATH'),
        'ranking_path' => env('SALES_RANKING_PATH'),
    ],

    'catalog' => [
        'url' => env('CATALOG_SERVICE_URL'),
        'token' => env('CATALOG_SERVICE_TOKEN'),
        'auth_required' => false,
        'products_path' => env('CATALOG_PRODUCTS_PATH', '/api/v1/productos'),
    ],

    // Consumidor G1 independiente del contrato de pagos confirmados de billing.
    'rendiciones' => [
        'url' => env('G1_RENDICIONES_URL'),
        'token' => env('G1_RENDICIONES_TOKEN'),
        'auth_required' => true,
        'path' => env('G1_RENDICIONES_PATH', '/api/v1/rendiciones'),
    ],

    'billing' => [
        'url' => env('BILLING_SERVICE_URL'),
        'token' => env('BILLING_SERVICE_TOKEN'),
        'payments_path' => env('BILLING_PAYMENTS_PATH', '/api/v1/cobros'),
        'invoices_path' => env('BILLING_INVOICES_PATH', '/api/v1/facturas/pendientes'),
    ],

    'balances' => [
        'url' => env('BALANCE_SERVICE_URL'),
        'token' => env('BALANCE_SERVICE_TOKEN'),
        'client_path' => env('BALANCE_CLIENT_PATH', '/api/v1/clientes/{id}/saldo'),
        'debtors_path' => env('BALANCE_DEBTORS_PATH', '/api/v1/clientes/deudores'),
    ],
];
