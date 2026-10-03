<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/Interfaz/index.html');

// ─── S11 / PC07: páginas de consulta de vencimientos y reposición ──────────────
Route::view('/vencimientos', 'vencimientos')->name('vencimientos.index');
Route::view('/sugerencias-reposicion', 'sugerencias-reposicion')->name('sugerencias-reposicion.index');
Route::view('/dashboard-reposicion', 'dashboard-reposicion')->name('dashboard-reposicion.index');
