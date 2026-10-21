<?php

use App\Modules\Login\Middleware\AuthenticateApiRequest;
use App\Modules\Login\Middleware\EnsureApiAudience;
use App\Modules\Login\Middleware\EnsureApiUser;
use App\Modules\Login\Middleware\EnsureCurrentSession;
use App\Modules\Login\Middleware\EnsureRole;
use App\Http\Middleware\IdentidadUsuario;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'session.current' => EnsureCurrentSession::class,
            'role' => EnsureRole::class,
            'api.access' => AuthenticateApiRequest::class,
            'api.audience' => EnsureApiAudience::class,
            'api.user' => EnsureApiUser::class,
        ]);

        $middleware->appendToGroup('api', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            IdentidadUsuario::class,
        ]);
        $middleware->appendToGroup('web', [IdentidadUsuario::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()->route('dashboard')
                ->with('permission_error', 'No tiene permisos para acceder a esa pantalla.');
        });
    })->create();
