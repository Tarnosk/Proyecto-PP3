<?php

namespace App\Modules\Ventas\Requests;

use App\Modules\Ventas\Logging\VentaLogger;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * BASE DE LAS VALIDACIONES DEL MÓDULO VENTAS
 *
 * Un "FormRequest" es una clase que valida los datos ANTES de que lleguen
 * al controlador. Si algo está mal, Laravel corta ahí y responde 422 con
 * la lista de errores; el controlador ni se entera.
 *
 * Esta base agrega una sola cosa: si la validación falla, lo anota en el
 * log de ventas (storage/logs/ventas/), para saber qué mandó el front.
 */
abstract class VentaRequest extends FormRequest
{
    /**
     * Título que aparece en el log, ej: "MODIFICAR VENTA".
     */
    abstract protected function tituloDeLog(): string;

    /**
     * Cualquiera puede usar estas rutas por ahora (no hay login todavía).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Se ejecuta cuando la validación falla: anota en el log y responde 422.
     */
    protected function failedValidation(Validator $validator): void
    {
        $log = new VentaLogger($this, $this->tituloDeLog(), class_basename((string) $this->route()?->getActionName()), 1);
        $log->paso('Validación del formato de los datos', 'Se comprueba que lleguen todos los campos obligatorios y con el tipo correcto.');
        $log->rechazada(new ValidationException($validator));

        parent::failedValidation($validator);
    }
}
