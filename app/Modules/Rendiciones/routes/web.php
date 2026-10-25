<?php

use App\Modules\Rendiciones\Controllers\RendicionController;
use App\Modules\Shared\Http\Middleware\G2Access;
use App\Modules\Shared\Http\Middleware\G2OwnsRecord;
use App\Modules\Shared\Http\Middleware\G2SchemaReady;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'session.current', G2Access::class.':admin,repartidor', G2SchemaReady::class.':rendiciones'])->group(function () {
    Route::view('/rendiciones', 'rendiciones.index')->name('rendiciones.index');
    Route::prefix('g2/api/rendiciones')->group(function () {
        Route::get('/', [RendicionController::class, 'index']);
        Route::get('/historial', [RendicionController::class, 'index']);
        Route::get('/preparar/{idEntrega}', [RendicionController::class, 'preparar'])->whereNumber('idEntrega')->middleware(G2OwnsRecord::class.':preparar');
        Route::get('/{id}', [RendicionController::class, 'show'])->whereNumber('id')->middleware(G2OwnsRecord::class.':rendicion');
        Route::post('/', [RendicionController::class, 'store']);
        Route::post('/cobros', [RendicionController::class, 'agregarCobroDirecto'])->middleware(G2OwnsRecord::class.':rendicion');
        Route::post('/devoluciones', [RendicionController::class, 'registrarDevolucionDirecta'])->middleware(G2OwnsRecord::class.':rendicion');
        Route::post('/diferencias', [RendicionController::class, 'registrarDiferenciaDirecta'])->middleware(G2OwnsRecord::class.':rendicion');
        Route::post('/validar', [RendicionController::class, 'validarDirecta'])->middleware([G2Access::class.':admin', G2OwnsRecord::class.':rendicion']);
        Route::post('/{id}/cobros', [RendicionController::class, 'agregarCobro'])->whereNumber('id')->middleware(G2OwnsRecord::class.':rendicion');
        Route::post('/{id}/devoluciones', [RendicionController::class, 'registrarDevolucion'])->whereNumber('id')->middleware(G2OwnsRecord::class.':rendicion');
        Route::post('/{id}/diferencias', [RendicionController::class, 'registrarDiferencia'])->whereNumber('id')->middleware(G2OwnsRecord::class.':rendicion');
        Route::patch('/{id}/cerrar', [RendicionController::class, 'cerrar'])->whereNumber('id')->middleware(G2OwnsRecord::class.':rendicion');
        Route::patch('/{id}/revisar', [RendicionController::class, 'revisar'])->whereNumber('id')->middleware([G2Access::class.':admin', G2OwnsRecord::class.':rendicion']);
    });
});
