<?php

use App\Modules\CuentaCorriente\Controllers\CuentaCorrienteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Módulo Cuenta Corriente (G4)
|--------------------------------------------------------------------------
| Las carga solo App\Providers\ModuleServiceProvider (middleware "web").
| Antes estaban en routes/web.php: se mudaron sin cambiar URL ni nombre.
*/

Route::get('/saldo', [CuentaCorrienteController::class, 'index'])->name('saldo');
Route::post('/saldo/{cliente}/movimientos', [CuentaCorrienteController::class, 'registrarMovimiento'])->name('saldo.movimientos');
