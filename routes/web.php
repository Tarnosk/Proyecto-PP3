<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Distribuidora Pigüé (ERP Grupo 3)
|--------------------------------------------------------------------------
|
| Rutas web del frontend en Laravel Blade con estilo Claymorfismo.
|
*/

// Panel Principal / Inicio
Route::view('/', 'dashboard')->name('dashboard');
Route::redirect('/dashboard', '/');

// Módulos Principales de Grupo 3
Route::view('/inventario', 'inventario.index')->name('inventario.index');
Route::redirect('/productos', '/inventario')->name('productos.index');
Route::redirect('/stock', '/inventario')->name('stock.index');

Route::view('/compras', 'compras.index')->name('compras.index');
Route::redirect('/pedidos-compra', '/compras')->name('pedidos-compra.index');
Route::redirect('/pedido-compra', '/compras');
Route::redirect('/pedido_compra', '/compras');
Route::redirect('/pedidos_compra', '/compras');
Route::redirect('/ordenes-compra', '/compras');
Route::redirect('/ordenes_compra', '/compras');
Route::redirect('/ordenes', '/compras');

Route::view('/proveedores', 'proveedores.index')->name('proveedores.index');
Route::redirect('/clientes', '/proveedores')->name('clientes.index');

// Submódulos S11 / PC07
Route::view('/vencimientos', 'vencimientos')->name('vencimientos.index');
Route::view('/sugerencias-reposicion', 'sugerencias-reposicion')->name('sugerencias-reposicion.index');
Route::view('/dashboard-reposicion', 'dashboard-reposicion')->name('dashboard-reposicion.index');
Route::redirect('/reposicion', '/sugerencias-reposicion');
