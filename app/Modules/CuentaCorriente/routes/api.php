<?php

use App\Modules\CuentaCorriente\Controllers\CuentaCorrienteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Módulo Cuenta Corriente
|--------------------------------------------------------------------------
| Las carga App\Providers\ModuleServiceProvider, con prefijo /api y middleware "api".
*/

Route::post('/cuenta-corriente/cobros', [CuentaCorrienteController::class, 'registrarCobro'])
    ->middleware('api.access')
    ->name('api.cuenta-corriente.cobros');
