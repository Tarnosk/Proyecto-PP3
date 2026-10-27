<?php

use App\Http\Middleware\PresentG2Rendiciones;
use App\Modules\Login\Middleware\AuthenticateApiRequest;
use App\Modules\Login\Middleware\EnsureApiAudience;
use App\Modules\Login\Middleware\EnsureApiUser;
use App\Modules\Login\Middleware\EnsureCurrentSession;
use App\Modules\Login\Middleware\EnsureRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [PresentG2Rendiciones::class]);
        $middleware->alias([
            'session.current' => EnsureCurrentSession::class,
            'role' => EnsureRole::class,
            'api.access' => AuthenticateApiRequest::class,
            'api.audience' => EnsureApiAudience::class,
            'api.user' => EnsureApiUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->expectsJson() && $request->isMethod('GET') && $request->routeIs('reports.*')
                && in_array($exception->getStatusCode(), [502, 503], true)) {
                return response()->view('reports.unavailable', [], $exception->getStatusCode());
            }

            return null;
        });
        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()->route('dashboard')
                ->with('permission_error', 'No tiene permisos para acceder a esa pantalla.');
        });
    })->create();
