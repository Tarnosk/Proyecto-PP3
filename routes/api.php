<?php

use App\Modules\Catalogo\Controllers\CatalogoApiController;
use App\Modules\Clientes\Controllers\Api\V1\ClientController;
use App\Modules\Clientes\Controllers\Api\V1\ZoneController;
use App\Modules\Login\Controllers\Api\V1\AuthController;
use App\Modules\Login\Controllers\Api\V1\UserController;
use App\Modules\Login\Middleware\AuthenticateApiReadRequest;
use App\Modules\Reclamos\Controllers\Api\V1\ClaimController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/catalogo', [CatalogoApiController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/catalogo/{producto}', [CatalogoApiController::class, 'show'])->where('producto', '[1-9][0-9]{0,9}')->middleware('throttle:60,1');
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware([AuthenticateApiReadRequest::class, 'throttle:60,1'])->group(function (): void {
        Route::get('/health', fn () => response()->json(['status' => 'ok'])
            ->header('Cache-Control', 'no-store'));
        Route::get('/clientes', [ClientController::class, 'index'])->middleware('api.audience:administrativo,repartidor');
        Route::get('/clientes/{cliente}', [ClientController::class, 'show'])->whereNumber('cliente')->middleware('api.audience:administrativo,repartidor');
        Route::get('/zonas', ZoneController::class)->middleware('api.audience:administrativo,repartidor');
        Route::get('/reclamos', [ClaimController::class, 'index'])->middleware('api.audience:administrativo,repartidor');
        Route::get('/reclamos/{reclamo}', [ClaimController::class, 'show'])->whereNumber('reclamo')->middleware('api.audience:administrativo,repartidor');
        Route::get('/usuarios/{user}', UserController::class)->whereNumber('user')->middleware('api.audience:administrativo');
    });

    Route::middleware(['api.access', 'throttle:60,1'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('api.user');

        Route::post('/clientes', [ClientController::class, 'store'])->middleware('api.user:administrativo');
        Route::put('/clientes/{cliente}', [ClientController::class, 'replace'])->whereNumber('cliente')->middleware('api.user:administrativo');
        Route::patch('/clientes/{cliente}', [ClientController::class, 'patch'])->whereNumber('cliente')->middleware('api.user:administrativo');
        Route::get('/clientes/{cliente}/saldo', [ClientController::class, 'balance'])->whereNumber('cliente')->middleware('api.audience:administrativo,repartidor');
        Route::get('/clientes/{cliente}/cobros', [ClientController::class, 'payments'])->whereNumber('cliente')->middleware('api.audience:administrativo,repartidor');
        Route::post('/reclamos', [ClaimController::class, 'store'])->middleware('api.audience:administrativo,repartidor');
        Route::patch('/reclamos/{reclamo}/estado', [ClaimController::class, 'updateStatus'])->whereNumber('reclamo')->middleware('api.audience:administrativo,repartidor');

    });
});
