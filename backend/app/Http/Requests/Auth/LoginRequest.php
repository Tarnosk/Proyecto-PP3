<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['usuario' => trim((string) $this->input('usuario'))]);
    }

    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'Debe completar usuario y contraseña.',
            'password.required' => 'Debe completar usuario y contraseña.',
        ];
    }
}
