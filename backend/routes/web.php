<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Distribuidora PyB (ERP Grupo 3)
|--------------------------------------------------------------------------
*/

// Redirección inicial según estado de autenticación
Route::get('/', function () {
    return auth()->check()
        ? view('dashboard')
        : redirect()->route('login');
});

// Rutas de invitados (Sin autenticar)
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('login.store');
});

// Cierre de sesión
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Rutas protegidas por Autenticación y Control de Sesión Única / Inactividad
Route::middleware(['auth', 'session.current'])->group(function (): void {
    // Panel Principal (Accesible para todos los roles: administrativo, repartidor, contador)
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Rutas exclusivas para ADMINISTRATIVO
    Route::middleware('role:administrativo')->group(function (): void {
        Route::get('/usuarios', function () {
            return response('Lista de Usuarios');
        })->name('users.index');
    });

    // Rutas para ADMINISTRATIVO y CONTADOR (Reportes y Finanzas)
    Route::middleware('role:administrativo,contador')->group(function (): void {
        Route::get('/reportes', function () {
            return response('Reportes contables');
        })->name('reports.index');
    });

    // Rutas para ADMINISTRATIVO y REPARTIDOR (Ventas, Stock, Inventario, Compras y Clientes)
    Route::middleware('role:administrativo,repartidor')->group(function (): void {
        // Clientes y Proveedores
        Route::view('/proveedores', 'proveedores.index')->name('proveedores.index');
        Route::view('/clientes', 'proveedores.index')->name('clientes.index');

        // Módulo Inventario y Stock (G3)
        Route::view('/inventario', 'inventario.index')->name('inventario.index');
        Route::redirect('/productos', '/inventario')->name('productos.index');
        Route::view('/stock', 'inventario.index')->name('stock.index');

        // Módulo Ventas (G4 / Alias de integración)
        Route::get('/ventas', function () {
            return response('Ventas y Pedidos');
        })->name('ventas.index');

        // Módulo Compras y Órdenes (G3)
        Route::view('/compras', 'compras.index')->name('compras.index');
        Route::redirect('/pedidos-compra', '/compras')->name('pedidos-compra.index');
        Route::redirect('/pedido-compra', '/compras');
        Route::redirect('/pedido_compra', '/compras');
        Route::redirect('/pedidos_compra', '/compras');
        Route::redirect('/ordenes-compra', '/compras');
        Route::redirect('/ordenes_compra', '/compras');
        Route::redirect('/ordenes', '/compras');

        // Submódulos Reposición y Vencimientos
        Route::redirect('/vencimientos', '/inventario')->name('vencimientos.index');
        Route::view('/sugerencias-reposicion', 'sugerencias-reposicion')->name('sugerencias-reposicion.index');
        Route::view('/dashboard-reposicion', 'dashboard-reposicion')->name('dashboard-reposicion.index');
        Route::redirect('/reposicion', '/sugerencias-reposicion');
    });
});
