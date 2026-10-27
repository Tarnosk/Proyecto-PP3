<?php

namespace App\Modules\Clientes\Requests\Api;

use App\Modules\Clientes\Services\ClientFields;
use App\Modules\Login\Enums\UserRole;
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
        $data = ClientFields::prepare($this->all(), inferDocument: true);
        if (isset($data['direccion_calle'], $data['direccion_numero'])
            && is_string($data['direccion_calle']) && is_string($data['direccion_numero'])
            && ! array_key_exists('direccion', $data)) {
            $data['direccion'] = $data['direccion_calle'].' '.$data['direccion_numero'];
        }
        $this->merge($data);
    }

    public function rules(): array
    {
        $isPatch = $this->isMethod('patch');
        $isCreate = $this->isMethod('post');
        $required = $isPatch ? ['sometimes', 'required'] : ['required'];
        $optional = $isPatch || $isCreate ? ['sometimes', 'nullable'] : ['present', 'nullable'];
        $client = $this->route('cliente');
        $type = $this->input('tipo_documento', $client?->tipo_documento);
        $shared = ClientFields::rules($client, is_string($type) ? $type : null);
        $requiredRules = fn (string $field) => [...($isPatch ? ['sometimes'] : []), ...$shared[$field]];

        return [
            'nombre' => $requiredRules('nombre'),
            'apellido_razon_social' => $requiredRules('apellido_razon_social'),
            'tipo_documento' => ['sometimes', Rule::in(['dni', 'cuit'])],
            'dni_cuit' => $requiredRules('dni_cuit'),
            'telefono' => $requiredRules('telefono'),
            'email' => $requiredRules('email'),
            'direccion' => [...$required, 'string', 'max:255'],
            'tipo_cliente' => [...$required, Rule::in(['minorista', 'mayorista'])],
            'estado' => $isCreate ? ['prohibited'] : [...$required, Rule::in(['activo', 'inactivo'])],
            'direccion_calle' => ['sometimes', 'required', 'string', 'max:200', 'regex:'.ClientFields::STREET, 'required_with:direccion_numero'],
            'direccion_numero' => ['sometimes', 'required', 'string', 'regex:/^[0-9]{1,6}$/', 'required_with:direccion_calle'],
            'localidad' => [...$optional, 'string', 'max:120', 'regex:'.ClientFields::PLACE],
            'zona' => ['sometimes', 'required', 'string', 'max:120', 'regex:'.ClientFields::ZONE, 'prohibits:zona_id'],
            'zona_id' => [...$optional, 'integer', Rule::exists('zonas', 'id')],
            'condicion_iva' => [...$optional, 'string', 'max:60'],
            'saldo' => ['prohibited'],
            'creado_por' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return ClientFields::messages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->has('tipo_documento') && ! $this->has('dni_cuit') && $this->route('cliente')) {
                $existing = $this->route('cliente');
                if ($this->input('tipo_documento') !== ClientFields::documentType($existing->dni_cuit)) {
                    $validator->errors()->add('dni_cuit', 'Indique un número que corresponda al tipo de documento seleccionado.');
                }
            }
            if ($this->hasAny(['direccion_calle', 'direccion_numero'])) {
                if (! $this->filled('direccion_calle') || ! $this->filled('direccion_numero')) {
                    $validator->errors()->add('direccion', 'Indique tanto la calle como su número.');
                } elseif ($this->input('direccion') !== $this->input('direccion_calle').' '.$this->input('direccion_numero')) {
                    $validator->errors()->add('direccion', 'La dirección completa no coincide con la calle y el número indicados.');
                }
            }
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
