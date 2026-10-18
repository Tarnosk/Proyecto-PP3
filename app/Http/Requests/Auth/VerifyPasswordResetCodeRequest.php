<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordResetCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => trim((string) $this->input('code'))]);
    }

    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Ingrese el código recibido por email.',
            'code.digits' => 'El código debe tener 6 números.',
        ];
    }
}
