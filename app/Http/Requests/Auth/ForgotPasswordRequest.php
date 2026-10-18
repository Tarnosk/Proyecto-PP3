<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:160']];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Ingrese el email asociado a su usuario.',
            'email.email' => 'Ingrese un email válido.',
        ];
    }
}
