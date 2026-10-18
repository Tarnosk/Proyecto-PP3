<?php

namespace App\Http\Requests\Api;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClientWriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === UserRole::ADMINISTRATIVO;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect($this->all())
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->all());
    }

    public function rules(): array
    {
        $isPatch = $this->isMethod('patch');
        $isCreate = $this->isMethod('post');
        $required = $isPatch ? ['sometimes', 'required'] : ['required'];
        $optional = $isPatch || $isCreate ? ['sometimes', 'nullable'] : ['present', 'nullable'];
        $client = $this->route('cliente');

        return [
            'nombre' => [...$required, 'string', 'max:120'],
            'apellido_razon_social' => [...$required, 'string', 'max:160'],
            'dni_cuit' => [...$required, 'string', 'max:20', 'regex:/^[0-9.\-\s]{6,20}$/', Rule::unique('clientes', 'dni_cuit')->ignore($client)],
            'telefono' => [...$required, 'string', 'max:30', 'regex:/^[0-9\s+\-()]{6,30}$/'],
            'email' => [...$required, 'email:rfc', 'max:160', Rule::unique('clientes', 'email')->ignore($client)],
            'direccion' => [...$required, 'string', 'max:255'],
            'tipo_cliente' => [...$required, Rule::in(['minorista', 'mayorista'])],
            'estado' => $isCreate ? ['prohibited'] : [...$required, Rule::in(['activo', 'inactivo'])],
            'localidad' => [...$optional, 'string', 'max:120'],
            'zona_id' => [...$optional, 'integer', Rule::exists('zonas', 'id')],
            'condicion_iva' => [...$optional, 'string', 'max:60'],
            'saldo' => ['prohibited'],
            'creado_por' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unexpected = array_diff(array_keys($this->all()), array_keys($this->rules()));
            foreach ($unexpected as $field) {
                $validator->errors()->add($field, 'El campo no forma parte de la ficha editable.');
            }

            if ($this->isMethod('patch') && $this->all() === []) {
                $validator->errors()->add('cliente', 'Debe indicar al menos un campo para modificar.');
            }
        });
    }
}
