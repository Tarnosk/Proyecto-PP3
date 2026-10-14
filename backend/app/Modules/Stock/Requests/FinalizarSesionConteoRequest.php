<?php
declare(strict_types=1);

namespace App\Modules\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizarSesionConteoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aplicar_ajustes' => ['nullable', 'boolean'],
        ];
    }
}
