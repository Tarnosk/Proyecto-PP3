<?php

namespace App\Modules\Ventas\Requests;

use App\Modules\Ventas\Enums\EstadoVenta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * VALIDACIÓN DE LOS FILTROS DE GET /api/ventas
 *
 * Todos opcionales, van en la URL:
 *   /api/ventas?estado=confirmada&cliente_id=472891&desde=2026-10-01&hasta=2026-10-31&texto=POS&por_pagina=20&page=2
 *
 * No extiende VentaRequest porque solo LEE: no hace falta dejarlo en el log.
 */
class ListarVentasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::enum(EstadoVenta::class)],
            'cliente_id' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'texto' => ['nullable', 'string', 'max:100'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:'.config('ventas.por_pagina_max')],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.enum' => 'Estado inválido. Valores posibles: '.implode(', ', EstadoVenta::valores()).'.',
            'desde.date_format' => 'La fecha "desde" tiene que tener el formato AAAA-MM-DD.',
            'hasta.date_format' => 'La fecha "hasta" tiene que tener el formato AAAA-MM-DD.',
            'hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a "desde".',
            'por_pagina.max' => 'Se pueden pedir como máximo '.config('ventas.por_pagina_max').' ventas por página.',
        ];
    }
}
