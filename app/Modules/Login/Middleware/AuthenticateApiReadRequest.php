<?php

namespace App\Modules\Login\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Autenticación dual limitada a las consultas GET/HEAD de G1. */
class AuthenticateApiReadRequest implements AuthenticatesRequests
{
    public function __construct(private AuthenticateApiRequest $bearer, private EnsureCurrentSession $session) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Un Bearer explícito nunca se sustituye por una cookie, aunque sea inválido.
        if ($request->headers->has('Authorization') || ! $request->isMethodSafe()) {
            return $this->noStore($this->bearer->handle($request, $next));
        }

        $request->headers->set('Accept', 'application/json');

        return $this->noStore(app(Pipeline::class)->send($request)->through([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ])->then(function (Request $request) use ($next): Response {
            $user = Auth::guard('web')->user();
            if (! $user) {
                return response()->json(['message' => 'No autorizado.'], 401);
            }

            $request->setUserResolver(fn () => $user);

            // Mismos controles de actividad, reemplazo e inactividad que el frontend.
            return $this->session->handle($request, $next);
        }));
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
