<?php

use App\Http\Middleware\AuthenticateApiRequest;
use App\Http\Middleware\EnsureApiAudience;
use App\Http\Middleware\EnsureApiUser;
use App\Http\Middleware\EnsureCurrentSession;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
