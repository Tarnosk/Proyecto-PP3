<?php

declare(strict_types=1);

use App\Http\Middleware\IdentidadUsuario;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Módulos Grupo 3 (Productos, Stock, Proveedores, Compras)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'session.current', IdentidadUsuario::class])->group(function (): void {
    Route::middleware('role:administrativo,repartidor')->group(function (): void {
        // Panel G3
        Route::view('/panel-g3', 'dashboard-g3')->name('g3.dashboard');

        // Módulo Proveedores (G3)
        Route::view('/proveedores', 'proveedores.index')->name('proveedores.index');

        // Módulo Inventario y Stock (G3)
        Route::view('/inventario', 'inventario.index')->name('inventario.index');
        Route::redirect('/productos', '/inventario')->name('productos.index');

        // Módulo Compras y Órdenes (G3)
        Route::view('/compras', 'compras.index')->name('compras.index');
        Route::redirect('/pedidos-compra', '/compras')->name('pedidos-compra.index');
        Route::redirect('/pedido-compra', '/compras');
        Route::redirect('/pedido_compra', '/compras');
        Route::redirect('/pedidos_compra', '/compras');
        Route::redirect('/ordenes-compra', '/compras');
        Route::redirect('/ordenes_compra', '/compras');
        Route::redirect('/ordenes', '/compras');

        // Submódulos Reposición y Vencimientos (G3)
        Route::redirect('/vencimientos', '/inventario')->name('vencimientos.index');
        Route::view('/sugerencias-reposicion', 'sugerencias-reposicion')->name('sugerencias-reposicion.index');
        Route::view('/dashboard-reposicion', 'dashboard-reposicion')->name('dashboard-reposicion.index');
        Route::redirect('/reposicion', '/sugerencias-reposicion');
    });
});
