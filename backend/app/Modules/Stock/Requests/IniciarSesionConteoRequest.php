<?php
declare(strict_types=1);

namespace App\Modules\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IniciarSesionConteoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_conteo' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
