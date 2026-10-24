<?php

namespace App\Modules\Ventas\Requests;

/**
 * VALIDACIÓN DE DELETE /api/ventas/{id} (botón "Eliminar" = anular)
 *
 * { "motivo": "El cliente canceló el pedido" }   ← obligatorio, 5 a 150 caracteres
 *
 * El motivo es obligatorio porque una anulación tiene que poder explicarse
 * después (queda guardado en venta_historial).
 */
class AnularVentaRequest extends VentaRequest
{
    protected function tituloDeLog(): string
    {
        return 'ANULAR VENTA';
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Indicá el motivo de la anulación.',
            'motivo.min' => 'El motivo tiene que tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo no puede superar los 150 caracteres.',
        ];
    }

    /**
     * Antes de validar se sacan los espacios de los costados ("   " no cuenta como motivo).
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('motivo'))) {
            $this->merge(['motivo' => trim($this->input('motivo'))]);
        }
    }
}
