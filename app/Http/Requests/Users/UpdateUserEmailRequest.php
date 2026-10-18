<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserEmailRequest extends FormRequest
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
        return [
            'email' => [
                'required',
                'email',
                'max:160',
                Rule::unique('usuarios', 'email')->ignore($this->route('user')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Ingrese un email para el usuario.',
            'email.email' => 'Ingrese un email válido.',
            'email.unique' => 'Ese email ya está asignado a otro usuario.',
        ];
    }
}
