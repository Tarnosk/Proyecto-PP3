<?php

use App\Modules\ConciliacionBancaria\Controllers\ChequeController;
use App\Modules\ConciliacionBancaria\Controllers\CobranzaErpController;
use App\Modules\ConciliacionBancaria\Controllers\ConciliacionController;
use App\Modules\ConciliacionBancaria\Controllers\ConciliacionWebController;
use App\Modules\ConciliacionBancaria\Controllers\MovimientoBancarioController;
use App\Modules\Shared\Http\Middleware\G2Access;
use App\Modules\Shared\Http\Middleware\G2SchemaReady;
use Illuminate\Support\Facades\Route;

// Rutas de interfaz web (Blade) protegidas por sesión
Route::middleware(['auth', 'session.current', G2Access::class.':admin,contador,administrativo', G2SchemaReady::class.':conciliacion'])->group(function () {
    Route::prefix('conciliacion')->name('conciliacion.')->group(function () {
        Route::get('/', [ConciliacionWebController::class, 'index'])->name('index');
        Route::get('/split', fn() => view('conciliacion.split-screen'))->name('split');
        Route::get('/nuevo', [ConciliacionWebController::class, 'create'])->name('create');
        Route::post('/mes', [ConciliacionWebController::class, 'asegurarMes'])->name('mes');
        Route::post('/', [ConciliacionWebController::class, 'store'])->name('store');
        Route::get('/{id}', [ConciliacionWebController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/conciliar', [ConciliacionWebController::class, 'conciliarAutomatico'])->whereNumber('id')->name('conciliar');
        Route::post('/movimientos/{id}/conciliar-manual', [ConciliacionWebController::class, 'conciliarManual'])->whereNumber('id')->name('conciliar-manual');
        Route::patch('/{id}/cerrar', [ConciliacionWebController::class, 'cerrar'])->whereNumber('id')->name('cerrar');
    });

    Route::prefix('g2/api')->group(function () {
        Route::get('/conciliacion', [ConciliacionController::class, 'index']);
        Route::post('/conciliacion', [ConciliacionController::class, 'store']);
        Route::get('/conciliacion/{id}', [ConciliacionController::class, 'show'])->whereNumber('id');
        Route::patch('/conciliacion/{id}/conciliar', [ConciliacionController::class, 'conciliarAutomatico'])->whereNumber('id');
        Route::post('/conciliacion/movimientos/{id}/conciliar-manual', [ConciliacionController::class, 'conciliarManual'])->whereNumber('id');
        Route::patch('/conciliacion/{id}/cerrar', [ConciliacionController::class, 'cerrar'])->whereNumber('id');
        Route::get('/cheques', [ChequeController::class, 'index']);
        Route::get('/cheques/{id}', [ChequeController::class, 'show'])->whereNumber('id');
        Route::patch('/cheques/{id}/depositar', [ChequeController::class, 'depositar'])->whereNumber('id');
        Route::patch('/cheques/{id}/rechazar', [ChequeController::class, 'rechazar'])->whereNumber('id');
    });
});

// APIs específicas del módulo de Conciliación Bancaria (HU #15 y Split Screen)
// Matriz de endpoints según normativa de inmutabilidad:
// GET /api/movimientos-bancarios
// GET /api/movimientos-bancarios/{id}
// POST /api/extractos/importar
// GET /api/cobranzas-erp
// GET /api/cobranzas-erp/{id}
Route::middleware(['web'])->group(function () {
    Route::get('/api/movimientos-bancarios', [MovimientoBancarioController::class, 'index']);
    Route::get('/api/movimientos-bancarios/{id}', [MovimientoBancarioController::class, 'show'])->whereNumber('id');
    Route::post('/api/extractos/importar', [MovimientoBancarioController::class, 'importar']);
    Route::get('/api/cobranzas-erp', [CobranzaErpController::class, 'index']);
    Route::get('/api/cobranzas-erp/{id}', [CobranzaErpController::class, 'show'])->whereNumber('id');
});
