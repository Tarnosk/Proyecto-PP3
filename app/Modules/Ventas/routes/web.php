<?php

use App\Modules\Ventas\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Módulo Ventas (G4)
|--------------------------------------------------------------------------
| Pantallas del módulo: Punto de Venta e Historial de ventas.
| Las carga solo App\Providers\ModuleServiceProvider (con el middleware "web").
| Antes estaban en routes/web.php: se mudaron sin cambiar URL ni nombre.
*/

Route::get('/ventas', [VentaController::class, 'index'])->name('ventas');                       // Pantalla del POS
Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');                // Alta de venta desde el POS
Route::get('/historial-ventas', [VentaController::class, 'historial'])->name('ventas.historial'); // Historial de ventas
