<?php

namespace App\Modules\Ventas\Requests;

use App\Modules\Ventas\Enums\EstadoVenta;
use Illuminate\Validation\Rule;

/**
 * VALIDACIÓN DE PATCH /api/ventas/{id}/estado (avanzar de fase)
 *
 * { "estado": "preparada", "motivo": "opcional" }
 *
 * Acá solo se revisa que "estado" sea uno de los 7 estados que existen.
 * Si el CAMBIO está permitido (ej: no volver atrás) lo decide
 * VentaService::cambiarEstado() con las reglas de EstadoVenta.
 */
class CambiarEstadoVentaRequest extends VentaRequest
{
    protected function tituloDeLog(): string
    {
        return 'CAMBIAR ESTADO DE VENTA';
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::enum(EstadoVenta::class)],
            'motivo' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Indicá el nuevo estado.',
            'estado.enum' => 'Estado inválido. Valores posibles: '.implode(', ', EstadoVenta::valores()).'.',
            'motivo.max' => 'El motivo no puede superar los 150 caracteres.',
        ];
    }

    /**
     * Acepta "Pagada" o " pagada " igual que "pagada".
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('estado'))) {
            $this->merge(['estado' => strtolower(trim($this->input('estado')))]);
        }
    }
}
