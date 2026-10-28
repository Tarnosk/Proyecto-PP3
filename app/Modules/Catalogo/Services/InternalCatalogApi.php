<?php

namespace App\Modules\Catalogo\Services;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Facade;
use Throwable;

/** Consume exclusivamente la API GET de Productos registrada, sin HTTP a sí mismo. */
class InternalCatalogApi
{
    public function get(string $path, array $query = []): Response
    {
        abort_unless(preg_match('#^/api/(?:v1/)?productos(?:/[1-9][0-9]*)?$#D', $path), 503, 'Ruta de catálogo interno no permitida.');
        $app = app();
        $app->make(Kernel::class);
        $router = $app['router'];
        $request = Request::create(rtrim(config('app.url'), '/').$path, 'GET', $query);
        $request->headers->set('Accept', 'application/json');
        if (filled($token = config('services.catalog.token'))) {
            $request->headers->set('Authorization', 'Bearer '.$token);
        }

        $route = clone $router->getRoutes()->match($request);
        $route->setContainer($app);
        abort_unless(in_array($route->getActionName(), [
            'App\\Modules\\Productos\\Controllers\\ProductoController@index',
            'App\\Modules\\Productos\\Controllers\\ProductoController@show',
        ], true), 503, 'La ruta no pertenece a la API de Productos esperada.');

        // La llamada es de servicio: no transmite cookies ni identidad del visitante.
        // Se omiten solo componentes de cookies/sesión, no autenticación ni permisos.
        $route->withoutMiddleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class]);
        $request->setRouteResolver(fn () => $route);
        $keys = ['request', 'auth', 'auth.driver', 'session', 'session.store'];
        $previous = [];
        foreach ($keys as $key) {
            $previous[$key] = $app->resolved($key) ? $app->make($key) : null;
        }
        $outerResolver = $previous['request']?->getUserResolver();

        try {
            $sessions = new SessionManager($app);
            $sessions->extend(config('session.driver') ?: 'array', fn () => new ArraySessionHandler((int) config('session.lifetime', 120)));
            $app->instance('session', $sessions);
            $app->instance('session.store', $sessions->driver());
            $auth = new AuthManager($app);
            $app->instance('auth', $auth);
            $app->forgetInstance('auth.driver');
            foreach (['auth', 'session', 'request'] as $accessor) {
                Facade::clearResolvedInstance($accessor);
            }
            $app->instance('request', $request);
            $request->setUserResolver(fn ($guard = null) => $auth->guard($guard)->user());

            try {
                $response = (new Pipeline($app))->send($request)
                    ->through($router->gatherRouteMiddleware($route))
                    ->then(fn (Request $request) => $router->prepareResponse($request, $route->run()));
            } catch (Throwable $error) {
                $response = $app->make(ExceptionHandler::class)->render($request, $error);
            }

            return new Response(new PsrResponse($response->getStatusCode(), $response->headers->all(), $response->getContent()));
        } finally {
            foreach (['auth', 'auth.driver', 'session', 'session.store', 'request'] as $key) {
                if ($previous[$key] !== null) {
                    $app->instance($key, $previous[$key]);
                } else {
                    $app->forgetInstance($key);
                }
            }
            if ($previous['request'] && $outerResolver) {
                $previous['request']->setUserResolver($outerResolver);
            }
            foreach (['auth', 'session', 'request'] as $accessor) {
                Facade::clearResolvedInstance($accessor);
            }
        }
    }
}
