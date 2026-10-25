<?php

use App\Modules\Entregas\Controllers\EntregaController;
use App\Modules\Entregas\Integraciones\VentasApiClient;
use App\Modules\Shared\Http\Middleware\G2Access;
use App\Modules\Shared\Http\Middleware\G2OwnsRecord;
use App\Modules\Shared\Http\Middleware\G2SchemaReady;
use App\Modules\Shared\Http\Response;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'session.current', G2Access::class.':admin,repartidor', G2SchemaReady::class.':entregas'])->group(function () {
    Route::view('/entregas', 'entregas.index')->name('entregas.index');
    Route::prefix('g2/api')->group(function () {
        Route::get('/entregas/pendientes-despacho', [EntregaController::class, 'pedidosPendientes'])->middleware(G2Access::class.':admin');
        Route::get('/entregas/pendientes-rendicion', [EntregaController::class, 'entregasPendientesRendicion']);
        Route::post('/entregas', [EntregaController::class, 'crearEntrega'])->middleware(G2Access::class.':admin');
        Route::get('/entregas', [EntregaController::class, 'listar']);
        Route::get('/entregas/{id}', [EntregaController::class, 'detalle'])->whereNumber('id')->middleware(G2OwnsRecord::class.':entrega');
        Route::patch('/entregas/{id}/pedidos/{idVenta}/entregado', [EntregaController::class, 'confirmarPedidoEntregado'])
            ->whereNumber(['id', 'idVenta'])->middleware(G2OwnsRecord::class.':entrega');
        Route::post('/entregas/confirmar-pedido-entregado', [EntregaController::class, 'confirmarPedidoEntregado'])->middleware(G2OwnsRecord::class.':entrega');
        Route::get('/remitos/{id}', [EntregaController::class, 'verRemito'])->whereNumber('id')->middleware(G2OwnsRecord::class.':remito');
        Route::get('/zonas', [EntregaController::class, 'zonas']);
        Route::get('/repartidores', [EntregaController::class, 'repartidores']);
        Route::get('/ventas/remotas', function (VentasApiClient $client) {
            try {
                $ventas = $client->obtenerVentasPendientesDespacho();

                return Response::json(['ventas' => $ventas, 'cantidad' => count($ventas), 'modo' => 'remoto_g4']);
            } catch (\Throwable $e) {
                report($e);

                return Response::error('La conexión con Ventas no está disponible. Contactá al administrador.', 503);
            }
        })->middleware(G2Access::class.':admin');
    });
});
