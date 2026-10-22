<?php

namespace App\Modules\Ventas\Exceptions;

use DomainException;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;

/**
 * ERROR DE NEGOCIO DEL MÓDULO VENTAS (clase base)
 *
 * Una "excepción" es la forma que tiene PHP de cortar lo que se está haciendo
 * y avisar "algo no se puede". Estas NO son bugs: son reglas de negocio
 * (ej: "no se puede modificar una venta pagada").
 *
 * Por qué extiende DomainException: es la excepción estándar de PHP para
 * "esto viola una regla del negocio".
 *
 * Por qué implementa ShouldntReport: como son errores ESPERADOS, Laravel no
 * los anota en storage/logs/laravel.log (no son fallas del sistema).
 *
 * Cada hija define su código HTTP y su código corto. Laravel llama solo al
 * método render() y devuelve el JSON al front: el controlador no tiene que
 * hacer nada especial.
 */
abstract class VentaException extends DomainException implements ShouldntReport
{
    /**
     * Código HTTP de la respuesta (404, 409, 503...).
     */
    abstract public function codigoHttp(): int;

    /**
     * Código corto y fijo para que el front pueda decidir qué mostrar
     * sin depender del texto del mensaje (ej: "VENTA_NO_EDITABLE").
     */
    abstract public function codigo(): string;

    /**
     * Datos extra para el JSON (ej: estado actual de la venta).
     */
    public function extra(): array
    {
        return [];
    }

    /**
     * Arma la respuesta JSON. La llama Laravel automáticamente.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'codigo' => $this->codigo(),
            ...$this->extra(),
        ], $this->codigoHttp());
    }
}
