<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Base troncal común)
|--------------------------------------------------------------------------
| Cada módulo registra sus propias rutas dentro de su carpeta:
| app/Modules/{Modulo}/routes/web.php (cargadas automáticamente por ModuleServiceProvider)
*/

Route::get('/', function () {
    return view('welcome');
});
