<?php

namespace App\Modules\Ventas\Requests;

use Illuminate\Validation\Rule;

/**
 * VALIDACIÓN DE PUT /api/ventas/{id} (botón "Modificar")
 *
 * Mismo formato que crear una venta (POST /ventas), SIN cliente:
 * {
 *   "lista": "minorista" | "mayorista",
 *   "observaciones": "texto opcional (máx. 150)",
 *   "descuento_global": { "modo": "porcentaje" | "monto", "valor": 10 },   ← opcional
 *   "items": [ { "id": 1, "cantidad": 2, "descuento": 100 } ]              ← 1 a 100 productos
 * }
 *
 * Que el producto exista y que el descuento no supere el precio lo revisa
 * después CalculadoraVenta (necesita el catálogo).
 */
class ModificarVentaRequest extends VentaRequest
{
    protected function tituloDeLog(): string
    {
        return 'MODIFICAR VENTA';
    }

    public function rules(): array
    {
        return [
            // El cliente NO se puede cambiar: si vino, se rechaza con un mensaje claro.
            'cliente_id' => ['prohibited'],
            'lista' => ['required', Rule::in(['minorista', 'mayorista'])],
            'observaciones' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.descuento' => ['required', 'numeric', 'min:0'],
            'descuento_global' => ['sometimes', 'array'],
            'descuento_global.modo' => ['required_with:descuento_global', Rule::in(['porcentaje', 'monto'])],
            'descuento_global.valor' => ['required_with:descuento_global', 'numeric', 'min:0'],
        ];
    }

    /**
     * Mensajes en castellano para los errores más comunes.
     */
    public function messages(): array
    {
        return [
            'cliente_id.prohibited' => 'El cliente de una venta no se puede cambiar. Anulá la venta y creá una nueva.',
            'lista.required' => 'Elegí la lista de precios (minorista o mayorista).',
            'lista.in' => 'La lista de precios tiene que ser minorista o mayorista.',
            'observaciones.max' => 'Las observaciones no pueden superar los 150 caracteres.',
            'items.required' => 'La venta tiene que tener al menos un producto.',
            'items.min' => 'La venta tiene que tener al menos un producto.',
            'items.max' => 'La venta no puede tener más de 100 productos.',
            'items.*.id.required' => 'Falta el id del producto.',
            'items.*.id.integer' => 'El id del producto tiene que ser un número.',
            'items.*.cantidad.required' => 'Falta la cantidad.',
            'items.*.cantidad.integer' => 'La cantidad tiene que ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad mínima es 1.',
            'items.*.cantidad.max' => 'La cantidad máxima es 9999.',
            'items.*.descuento.required' => 'Falta el descuento (mandá 0 si no tiene).',
            'items.*.descuento.min' => 'El descuento no puede ser negativo.',
            'descuento_global.modo.in' => 'El descuento global tiene que ser "porcentaje" o "monto".',
            'descuento_global.valor.min' => 'El descuento global no puede ser negativo.',
        ];
    }
}
