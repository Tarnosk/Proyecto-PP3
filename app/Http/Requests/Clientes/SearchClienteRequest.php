<?php

namespace App\Http\Requests\Clientes;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;

class SearchClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Cliente::class) ?? false;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:160']];
    }
}
