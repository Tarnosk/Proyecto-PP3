<?php

namespace App\Modules\Clientes\Requests;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Services\ClientFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $cliente instanceof Cliente && ($this->user()?->can('update', $cliente) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(ClientFields::prepare($this->all()));
    }

    public function rules(): array
    {
        $cliente = $this->route('cliente');
        $type = $this->input('tipo_documento');

        return [...ClientFields::rules($cliente, is_string($type) ? $type : null),
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return ClientFields::messages();
    }
}
