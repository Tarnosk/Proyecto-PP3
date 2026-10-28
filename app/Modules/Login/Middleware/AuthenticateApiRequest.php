<?php

namespace App\Modules\Login\Middleware;

use App\Modules\Login\Services\ApiTokenService;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiRequest implements AuthenticatesRequests
{
    public function __construct(private ApiTokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        $provided = $request->bearerToken();
        $internal = config('services.internal_api.token');

        if (is_string($internal) && $internal !== '' && is_string($provided) && hash_equals($internal, $provided)) {
            $request->attributes->set('api_service', true);
            $request->setUserResolver(fn () => null);

            return $next($request);
        }

        $user = $this->tokens->resolve($provided);
        if (! $user) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
