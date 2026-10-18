<?php

namespace App\Http\Requests\Clientes;

use App\Models\Cliente;
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
        $this->merge(collect($this->all())->map(fn ($value) => is_string($value) ? trim($value) : $value)->all());
    }

    public function rules(): array
    {
        $cliente = $this->route('cliente');

        return [
            'nombre' => ['required', 'string', 'max:120'],
            'apellido_razon_social' => ['required', 'string', 'max:160'],
            'dni_cuit' => ['required', 'string', 'max:20', 'regex:/^[0-9.\-\s]{6,20}$/', Rule::unique('clientes', 'dni_cuit')->ignore($cliente)],
            'telefono' => ['required', 'string', 'max:30', 'regex:/^[0-9\s+\-()]{6,30}$/'],
            'email' => ['required', 'email:rfc', 'max:160', Rule::unique('clientes', 'email')->ignore($cliente)],
            'localidad' => ['required', 'string', 'max:120'],
            'direccion' => ['required', 'string', 'max:255'],
            'tipo_cliente' => ['required', Rule::in(['minorista', 'mayorista'])],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Debe completar todos los campos obligatorios.',
            'dni_cuit.regex' => 'El DNI/CUIT ingresado no tiene un formato válido.',
            'dni_cuit.unique' => 'Ya existe otro cliente registrado con ese DNI/CUIT.',
            'telefono.regex' => 'El teléfono ingresado no tiene un formato válido.',
            'email.email' => 'El email ingresado no es válido.',
            'email.unique' => 'Ya existe otro cliente registrado con ese email.',
            'tipo_cliente.in' => 'El tipo de cliente seleccionado no es válido.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
