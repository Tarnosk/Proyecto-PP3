<?php

use App\Modules\Ventas\Controllers\VentaApiController;
use App\Modules\Ventas\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Módulo Ventas (G4)
|--------------------------------------------------------------------------
| Las carga solo App\Providers\ModuleServiceProvider, con prefijo /api y
| middleware "api" (todas las URLs empiezan con /api/ventas).
| Antes estaban en routes/api.php: se mudaron sin cambiar URL ni nombre.
| Guía completa: docs/VENTAS-BACKEND.md
*/

// Alta y catálogo simulado (VentaController, de develop-g4)
Route::get('/ventas/productos-simulados', [VentaController::class, 'obtenerProductosSimulados']); // Lo usa resources/js/promociones.js
Route::post('/ventas', [VentaController::class, 'store']);                                         // Alta de venta

// Consultar, modificar, anular y cambiar estado (VentaApiController)
// whereNumber('id') hace que {id} solo acepte números: así "/ventas/estados"
// o "/ventas/productos-simulados" nunca se confunden con el id de una venta.
Route::prefix('ventas')->controller(VentaApiController::class)->name('api.ventas.')->group(function () {
    Route::get('/', 'index')->name('index');                                              // Lista con filtros y paginación
    Route::get('/estados', 'estados')->name('estados');                                   // Catálogo de estados y sus reglas
    Route::get('/productos', 'productos')->name('productos');                             // Catálogo para "Agregar producto" al modificar
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');                         // Una venta con detalle, botones e historial
    Route::put('/{id}', 'update')->whereNumber('id')->name('update');                     // Botón "Modificar"
    Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');                // Botón "Eliminar" (anula, no borra)
    Route::post('/{id}/restaurar', 'restaurar')->whereNumber('id')->name('restaurar');    // Deshace una anulación
    Route::patch('/{id}/estado', 'cambiarEstado')->whereNumber('id')->name('estado');     // Avanza de fase
});
