<?php

namespace App\Http\Requests\Reclamos;

use App\Models\Reclamo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReclamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Reclamo::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asunto' => trim((string) $this->input('asunto')),
            'descripcion' => trim((string) $this->input('descripcion')),
            'prioridad' => trim((string) $this->input('prioridad')),
        ]);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('estado', 'activo')),
            ],
            'asunto' => ['required', 'string', 'max:160'],
            'descripcion' => ['required', 'string'],
            'prioridad' => ['required', Rule::in(['baja', 'media', 'alta'])],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Debe completar todos los campos obligatorios.',
            'cliente_id.exists' => 'El cliente seleccionado no existe o no está activo.',
            'asunto.max' => 'El asunto no puede superar los 160 caracteres.',
            'prioridad.in' => 'La prioridad seleccionada no es válida.',
        ];
    }
}
