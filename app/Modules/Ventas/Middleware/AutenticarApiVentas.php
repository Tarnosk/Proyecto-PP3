<?php

namespace App\Modules\Ventas\Middleware;

use App\Modules\Login\Middleware\AuthenticateApiReadRequest;
use App\Modules\Login\Middleware\EnsureCurrentSession;
use Closure;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * PROTEGE TODAS LAS APIs DE VENTAS (/api/ventas/...)
 *
 * Sin sesión iniciada ni token válido responde 401 "No autorizado." y no muestra nada.
 *
 *  - Con "Authorization: Bearer <token>" (Postman, Reportes, Entregas): valida el
 *    token del login (/api/v1/auth/login) o el token interno del sistema.
 *  - Sin token, desde el navegador: exige la sesión web iniciada.
 *    En las consultas (GET) alcanza con la sesión.
 *    En los cambios (POST, PUT, PATCH, DELETE) además exige el token CSRF
 *    (header X-CSRF-TOKEN), para que otra página no pueda modificar ventas
 *    usando la sesión del usuario. Sin CSRF responde 419.
 *
 * Las consultas y los pedidos con Bearer los resuelve el middleware de Login
 * (AuthenticateApiReadRequest); acá solo se agregan los cambios con sesión.
 */
class AutenticarApiVentas
{
    public function __construct(
        private readonly AuthenticateApiReadRequest $lectura,
        private readonly EnsureCurrentSession $sesion,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->headers->has('Authorization')) {
            return $this->lectura->handle($request, $next);
        }

        $request->headers->set('Accept', 'application/json');

        $respuesta = app(Pipeline::class)->send($request)->through([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ])->then(function (Request $request) use ($next): Response {
            $usuario = Auth::guard('web')->user();
            if (! $usuario) {
                return response()->json(['message' => 'No autorizado.'], 401);
            }

            $request->setUserResolver(fn () => $usuario);

            return app(Pipeline::class)->send($request)->through([
                ValidateCsrfToken::class,
                fn (Request $request, Closure $siguiente) => $this->sesion->handle($request, $siguiente),
            ])->then($next);
        });

        $respuesta->headers->set('Cache-Control', 'no-store');

        return $respuesta;
    }
}
