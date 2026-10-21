<?php

use App\Http\Controllers\DashboardController;
use App\Modules\Clientes\Controllers\ClienteController;
use App\Modules\Login\Controllers\AuthenticatedSessionController;
use App\Modules\Login\Controllers\PasswordRecoveryController;
use App\Modules\Login\Controllers\UserController;
use App\Modules\Reclamos\Controllers\ReclamoController;
use App\Modules\Reportes\Controllers\ReportController;
use App\Modules\Reportes\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Distribuidora PyB (ERP Integrado G1 + G3)
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('login.store');

    Route::get('/recuperar-contrasena', [PasswordRecoveryController::class, 'request'])
        ->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordRecoveryController::class, 'sendCode'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/recuperar-contrasena/codigo', [PasswordRecoveryController::class, 'code'])
        ->name('password.code');
    Route::post('/recuperar-contrasena/codigo', [PasswordRecoveryController::class, 'verifyCode'])
        ->middleware('throttle:10,1')
        ->name('password.code.verify');
    Route::get('/recuperar-contrasena/nueva', [PasswordRecoveryController::class, 'reset'])
        ->name('password.reset');
    Route::post('/recuperar-contrasena/nueva', [PasswordRecoveryController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'session.current'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:administrativo,repartidor')->group(function (): void {
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->whereNumber('cliente')->name('clientes.show');
        Route::get('/reclamos', [ReclamoController::class, 'index'])->name('reclamos.index');
        Route::get('/reclamos/nuevo', [ReclamoController::class, 'create'])->name('reclamos.create');
        Route::post('/reclamos', [ReclamoController::class, 'store'])->name('reclamos.store');

        // Módulo Proveedores (G3)
        Route::view('/proveedores', 'proveedores.index')->name('proveedores.index');

        // Módulo Inventario y Stock (G3)
        Route::view('/inventario', 'inventario.index')->name('inventario.index');
        Route::redirect('/productos', '/inventario')->name('productos.index');
        Route::view('/stock', 'inventario.index')->name('stock.index');

        // Módulo Ventas (G4 / Alias de integración)
        Route::view('/ventas', 'reports.external-module', ['module' => 'Ventas y pedidos', 'group' => 'G4'])
            ->name('ventas.index');

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

    Route::middleware('role:administrativo')->group(function (): void {
        Route::get('/clientes/nuevo', [ClienteController::class, 'create'])->name('clientes.create');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
        Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->whereNumber('cliente')->name('clientes.edit');
        Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->whereNumber('cliente')->name('clientes.update');
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::put('/usuarios/{user}/email', [UserController::class, 'updateEmail'])->name('users.email.update');
    });

    Route::middleware('role:administrativo,contador')->prefix('reportes')->name('reports.')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/historial-clientes', [ReportController::class, 'clientHistory'])->name('client-history');
        Route::get('/clientes-por-producto', [ReportController::class, 'clientProductReach'])->name('client-product-reach');
        Route::get('/clientes-deudores', [ReportController::class, 'debtors'])->name('debtors');
        Route::get('/ranking-comercial', [ReportController::class, 'productRanking'])->name('product-ranking');
        Route::get('/historial-clientes/exportar/{format}', [ReportExportController::class, 'clientHistory'])->name('client-history.export');
    });
});
