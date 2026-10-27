<?php

namespace App\Modules\Clientes\Requests;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Services\ClientFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Cliente::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(ClientFields::prepare($this->all()));
    }

    public function rules(): array
    {
        $type = $this->input('tipo_documento');

        return ClientFields::rules(null, is_string($type) ? $type : null);
    }

    public function messages(): array
    {
        return ClientFields::messages();
    }
}
