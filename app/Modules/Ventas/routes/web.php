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

Route::middleware(['auth', 'session.current', 'role:administrativo,repartidor'])->group(function (): void {
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::get('/historial-ventas', [VentaController::class, 'historial'])->name('ventas.historial');
});
