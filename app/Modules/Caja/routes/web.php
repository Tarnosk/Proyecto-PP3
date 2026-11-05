<?php

use App\Modules\Caja\Controllers\CajaController;
use App\Modules\Caja\Controllers\CajaWebController;
use App\Modules\Shared\Http\Middleware\G2Access;
use App\Modules\Shared\Http\Middleware\G2SchemaReady;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'session.current', G2SchemaReady::class.':caja'])->group(function () {

    // --- Lectura de cierres: admin, repartidor y contador ---------------------
    // (El repartidor solo ve los suyos; admin y contador ven todos.)
    // Van primero: '/cierres' debe resolverse antes que '/{fecha}' en la API.
    Route::middleware(G2Access::class.':admin,repartidor,contador')->group(function () {
        Route::get('/caja/cierres', [CajaWebController::class, 'cierresIndex'])->name('caja.cierres.index');
        Route::get('/caja/cierres/{id}', [CajaWebController::class, 'cierresShow'])->whereNumber('id')->name('caja.cierres.show');

        Route::get('/g2/api/caja/cierres', [CajaController::class, 'historialCierres']);
        Route::get('/g2/api/caja/cierres/{id}', [CajaController::class, 'detalleCierre'])->whereNumber('id');
    });

    // --- Exportación a PDF (solo lectura): admin y contador -------------------
    Route::middleware(G2Access::class.':admin,contador')->group(function () {
        Route::get('/caja/cierres/pdf', [CajaWebController::class, 'cierresPdf'])->name('caja.cierres.pdf');
        Route::get('/caja/cierres/{id}/pdf', [CajaWebController::class, 'cierrePdf'])->whereNumber('id')->name('caja.cierres.pdf.detalle');
    });

    // --- Operación de caja: admin y repartidor (el contador NO) ---------------
    Route::middleware(G2Access::class.':admin,repartidor')->group(function () {
        Route::prefix('caja')->name('caja.')->group(function () {
            Route::get('/', [CajaWebController::class, 'diaActual'])->name('dia');
            Route::post('/apertura', [CajaWebController::class, 'abrir'])->name('abrir');
            Route::post('/{id}/movimiento', [CajaWebController::class, 'registrarMovimiento'])->whereNumber('id')->name('movimiento');
            Route::patch('/{id}/cerrar', [CajaWebController::class, 'cerrar'])->whereNumber('id')->name('cerrar');
            Route::patch('/{id}/conteo', [CajaWebController::class, 'completarConteo'])->whereNumber('id')->middleware(G2Access::class.':admin')->name('conteo');
            Route::get('/pendiente/{id}', [CajaWebController::class, 'mostrarCaja'])->whereNumber('id')->name('pendiente');
        });

        // Endpoints de sesión para este frontend; no sustituyen APIs públicas de G1.
        Route::prefix('g2/api/caja')->group(function () {
            Route::get('/', [CajaController::class, 'listar']);
            Route::post('/apertura', [CajaController::class, 'abrir']);
            Route::post('/movimiento', [CajaController::class, 'registrarMovimiento']);
            Route::get('/movimiento/{id}', [CajaController::class, 'detalleMovimiento'])->whereNumber('id');
            Route::patch('/movimiento/{id}', [CajaController::class, 'modificarMovimiento'])->whereNumber('id')->middleware(G2Access::class.':admin');
            Route::patch('/{id}/cerrar', [CajaController::class, 'cerrar'])->whereNumber('id');
            Route::patch('/{id}/completar-conteo', [CajaController::class, 'completarConteo'])->whereNumber('id')->middleware(G2Access::class.':admin');
            Route::get('/{fecha}/{id_usuario}', [CajaController::class, 'porUsuarioYFecha'])->whereNumber('id_usuario');
            Route::get('/{fecha}', [CajaController::class, 'porFecha']);
        });
    });
});
