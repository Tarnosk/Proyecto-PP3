<?php

namespace App\Modules\Clientes\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DocumentNumber implements ValidationRule
{
    public function __construct(private ?string $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[0-9.\-\s]+$/u', $value)) {
            $fail('El documento solo admite números, sin letras ni símbolos.');

            return;
        }

        $digits = preg_replace('/\D/', '', $value);
        if ($this->type === 'dni' && ! preg_match('/^[0-9]{7,8}$/', $digits)) {
            $fail('El DNI debe tener 7 u 8 dígitos.');
        } elseif ($this->type === 'cuit' && ! preg_match('/^[0-9]{11}$/', $digits)) {
            $fail('El CUIT debe tener exactamente 11 dígitos.');
        } elseif (! in_array($this->type, ['dni', 'cuit'], true)) {
            $fail('Seleccione DNI o CUIT para validar el número.');
        }
    }
}
