<?php

use App\Modules\Caja\Controllers\CajaController;
use App\Modules\Caja\Controllers\CajaWebController;
use App\Modules\Shared\Http\Middleware\G2Access;
use App\Modules\Shared\Http\Middleware\G2SchemaReady;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'session.current', G2Access::class.':admin,repartidor', G2SchemaReady::class.':caja'])->group(function () {
    Route::prefix('caja')->name('caja.')->group(function () {
        Route::get('/', [CajaWebController::class, 'diaActual'])->name('dia');
        Route::post('/apertura', [CajaWebController::class, 'abrir'])->name('abrir');
        Route::post('/{id}/movimiento', [CajaWebController::class, 'registrarMovimiento'])->whereNumber('id')->name('movimiento');
        Route::patch('/{id}/cerrar', [CajaWebController::class, 'cerrar'])->whereNumber('id')->name('cerrar');
        Route::patch('/{id}/conteo', [CajaWebController::class, 'completarConteo'])->whereNumber('id')->middleware(G2Access::class.':admin')->name('conteo');
        Route::get('/cierres', [CajaWebController::class, 'cierresIndex'])->name('cierres.index');
        Route::get('/cierres/{id}', [CajaWebController::class, 'cierresShow'])->whereNumber('id')->name('cierres.show');
        Route::get('/pendiente/{id}', [CajaWebController::class, 'mostrarCaja'])->whereNumber('id')->name('pendiente');
    });
    // Endpoints de sesión para este frontend; no sustituyen APIs públicas de G1.
    Route::prefix('g2/api/caja')->group(function () {
        Route::get('/', [CajaController::class, 'listar']);
        Route::post('/apertura', [CajaController::class, 'abrir']);
        Route::get('/cierres', [CajaController::class, 'historialCierres']);
        Route::get('/cierres/{id}', [CajaController::class, 'detalleCierre'])->whereNumber('id');
        Route::post('/movimiento', [CajaController::class, 'registrarMovimiento']);
        Route::get('/movimiento/{id}', [CajaController::class, 'detalleMovimiento'])->whereNumber('id');
        Route::patch('/movimiento/{id}', [CajaController::class, 'modificarMovimiento'])->whereNumber('id')->middleware(G2Access::class.':admin');
        Route::patch('/{id}/cerrar', [CajaController::class, 'cerrar'])->whereNumber('id');
        Route::patch('/{id}/completar-conteo', [CajaController::class, 'completarConteo'])->whereNumber('id')->middleware(G2Access::class.':admin');
        Route::get('/{fecha}/{id_usuario}', [CajaController::class, 'porUsuarioYFecha'])->whereNumber('id_usuario');
        Route::get('/{fecha}', [CajaController::class, 'porFecha']);
    });
});
