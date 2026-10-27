<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Adaptación de presentación en G1; conserva las restricciones y el estado de G2. */
class PresentG2Rendiciones
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $request->isMethod('GET') || ! $request->routeIs('rendiciones.index') || $request->expectsJson()) {
            return $response;
        }
        if ($response->getStatusCode() === 503 && $request->user()) {
            $response = response()->view('rendiciones.index', ['g1RendicionesUnavailable' => true], 503);
        }
        if (in_array($response->getStatusCode(), [200, 503], true)) {
            // Etiquetas de la composición de G1, sin editar la vista aportada por G2.
            $response->setContent(str_replace(
                ['Control de rendiciones de choferes, cobros liquidados y arqueo de caja', 'Cobros Liquidados', 'Aprobadas (Cobros)'],
                ['Rendiciones y cobros registrados. La aprobación no certifica la liquidación individual de cada cobro.', 'Importe de rendiciones aprobadas hoy', 'Rendiciones aprobadas'],
                $response->getContent()
            ));
        }

        return $response;
    }
}
