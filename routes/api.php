<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Base troncal común)
|--------------------------------------------------------------------------
| Cada módulo registra sus propias rutas de API dentro de su carpeta:
| app/Modules/{Modulo}/routes/api.php (cargadas automáticamente con prefijo /api)
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});
