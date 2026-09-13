<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IdentidadUsuario;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Identidad del usuario que ejecuta cada acción (auditoría).
        // G1 resuelve el login; este middleware expone la identidad y, hasta
        // que G1 lo integre, el resolver UsuarioActual cae al admin (id=1).
        $middleware->appendToGroup('api', [IdentidadUsuario::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
