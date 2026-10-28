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

    protected function prepareForValidation(): void
    {
        if (!$this->has('cantidad_fisica') && $this->has('stock_fisico')) {
            $this->merge([
                'cantidad_fisica' => $this->input('stock_fisico'),
            ]);
        }
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
