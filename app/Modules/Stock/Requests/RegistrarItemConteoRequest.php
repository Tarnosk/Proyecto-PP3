<?php
declare(strict_types=1);

namespace App\Modules\Stock\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarItemConteoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_producto' => ['required', 'integer', 'exists:PRODUCTO,id_producto'],
            'cantidad_fisica' => ['required', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }
}
