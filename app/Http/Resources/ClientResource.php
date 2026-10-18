<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido_razon_social' => $this->apellido_razon_social,
            'dni_cuit' => $this->dni_cuit,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'localidad' => $this->localidad,
            'zona_id' => $this->zona_id,
            'condicion_iva' => $this->condicion_iva,
            'tipo_cliente' => $this->tipo_cliente->value,
            'estado' => $this->estado->value,
        ];
    }
}
