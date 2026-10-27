<?php

namespace App\Modules\Clientes\Services;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Rules\DocumentNumber;
use Closure;
use Illuminate\Validation\Rule;

class ClientFields
{
    public const NAME = "/^(?=.*\\p{L})[\\p{L}\\p{M} .’'\\-]+$/u";

    public const BUSINESS = "/^(?=.*[\\p{L}\\p{N}])[\\p{L}\\p{M}\\p{N} .,’'&()\\/\\-]+$/u";

    public const PLACE = "/^(?=.*\\p{L})[\\p{L}\\p{M}\\p{N} .,’'()\\/\\-]+$/u";

    public const ZONE = "/^(?=.*[\\p{L}\\p{N}])[\\p{L}\\p{M}\\p{N} .,’'()\\/\\-]+$/u";

    public const STREET = "/^(?=.*[\\p{L}\\p{N}])[\\p{L}\\p{M}\\p{N} .,’'°º()\\/\\-]+$/u";

    public const EMAIL = '/^[^\s@]+@(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i';

    public static function documentDigits(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[0-9.\-\s]+$/u', $value)
            ? preg_replace('/\D/', '', $value) : null;
    }

    public static function documentType(mixed $value): ?string
    {
        $digits = self::documentDigits($value);

        return match (strlen($digits ?? '')) {
            7, 8 => 'dni', 11 => 'cuit', default => null,
        };
    }

    public static function documentValue(mixed $value): mixed
    {
        $digits = self::documentDigits($value);
        if ($digits === null) {
            return $value;
        }

        // Se conserva el formato CUIT que ya devuelve la API a otros equipos.
        return strlen($digits) === 11
            ? substr($digits, 0, 2).'-'.substr($digits, 2, 8).'-'.substr($digits, 10)
            : $digits;
    }

    public static function prepare(array $data, bool $inferDocument = false): array
    {
        $data = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $data);
        if (isset($data['direccion_numero']) && is_int($data['direccion_numero'])) {
            $data['direccion_numero'] = (string) $data['direccion_numero'];
        }
        if (isset($data['tipo_documento']) && is_string($data['tipo_documento'])) {
            $data['tipo_documento'] = strtolower($data['tipo_documento']);
        }
        if (isset($data['dni_cuit']) && is_string($data['dni_cuit'])) {
            $data['dni_cuit'] = self::documentValue($data['dni_cuit']);
            if ($inferDocument && ! array_key_exists('tipo_documento', $data)) {
                $data['tipo_documento'] = self::documentType($data['dni_cuit']);
            }
        }
        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }
        foreach (['nombre', 'apellido_razon_social', 'localidad', 'zona', 'direccion_calle'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = preg_replace('/\s+/u', ' ', $data[$key]);
            }
        }

        return $data;
    }

    public static function addressParts(?string $address): array
    {
        if (is_string($address) && preg_match('/^(.+?)\s+([0-9]{1,6})$/u', trim($address), $parts)) {
            return ['direccion_calle' => $parts[1], 'direccion_numero' => $parts[2]];
        }

        return ['direccion_calle' => null, 'direccion_numero' => null];
    }

    public static function rules(?Cliente $client, ?string $documentType): array
    {
        return [
            'nombre' => ['bail', 'required', 'string', 'max:120', 'regex:'.self::NAME],
            'apellido_razon_social' => ['bail', 'required', 'string', 'max:160', 'regex:'.self::BUSINESS],
            'tipo_documento' => ['required', Rule::in(['dni', 'cuit'])],
            'dni_cuit' => ['bail', 'required', 'string', 'max:20', new DocumentNumber($documentType), Rule::unique('clientes', 'dni_cuit')->ignore($client)],
            'telefono' => ['bail', 'required', 'string', 'max:30', 'regex:/^\+?[0-9 ()\-]+$/', function (string $attribute, mixed $value, Closure $fail): void {
                $length = strlen(preg_replace('/\D/', '', $value));
                if ($length < 6 || $length > 15) {
                    $fail('El teléfono debe contener entre 6 y 15 dígitos.');
                }
            }],
            'email' => ['bail', 'required', 'string', 'max:160', 'email:rfc', 'regex:'.self::EMAIL, Rule::unique('clientes', 'email')->ignore($client)],
            'localidad' => ['bail', 'required', 'string', 'max:120', 'regex:'.self::PLACE],
            'zona' => ['bail', 'required', 'string', 'max:120', 'regex:'.self::ZONE],
            'direccion_calle' => ['bail', 'required', 'string', 'max:200', 'regex:'.self::STREET],
            'direccion_numero' => ['bail', 'required', 'string', 'regex:/^[0-9]{1,6}$/'],
            'tipo_cliente' => ['required', Rule::in(['minorista', 'mayorista'])],
        ];
    }

    public static function messages(): array
    {
        return [
            'required' => 'Complete este campo obligatorio.',
            'string' => 'Ingrese un texto válido.',
            'nombre.regex' => 'El nombre admite letras, espacios, puntos, apóstrofes y guiones.',
            'apellido_razon_social.regex' => 'El apellido o razón social contiene caracteres no permitidos.',
            'tipo_documento.in' => 'Seleccione DNI o CUIT.',
            'dni_cuit.unique' => 'Ya existe un cliente con ese DNI/CUIT.',
            'telefono.regex' => 'Use números; puede incluir + al inicio, espacios, paréntesis y guiones.',
            'email.email' => 'Ingrese un email válido, por ejemplo nombre@gmail.com.',
            'email.regex' => 'El email debe incluir @ y un dominio completo, por ejemplo empresa.com.ar.',
            'email.unique' => 'Ya existe un cliente con ese email.',
            'localidad.regex' => 'La localidad contiene caracteres no permitidos.',
            'zona.regex' => 'La zona debe contener un nombre válido, sin etiquetas ni símbolos especiales.',
            'direccion_calle.regex' => 'El nombre de la calle contiene caracteres no permitidos.',
            'direccion_numero.regex' => 'La altura debe tener entre 1 y 6 dígitos, sin letras ni signos.',
            'tipo_cliente.in' => 'Seleccione Minorista o Mayorista.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
