<?php

namespace App\Modules\Clientes\Services;

use App\Modules\Clientes\Enums\ClientStatus;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Models\Zona;
use Illuminate\Support\Facades\DB;

class ClientWriter
{
    public function save(array $data, ?Cliente $client = null, ?int $creator = null): Cliente
    {
        return DB::transaction(function () use ($data, $client, $creator): Cliente {
            if (isset($data['zona'])) {
                $name = $data['zona'];
                $zone = Zona::whereRaw('LOWER(nombre) = ?', [mb_strtolower($name)])->first()
                    ?? Zona::firstOrCreate(['nombre' => $name]);
                $data['zona_id'] = $zone->id;
            }
            unset($data['zona']);
            if ($client === null) {
                $client = new Cliente;
                $client->estado = ClientStatus::ACTIVO;
                $client->creado_por = $creator;
            }
            $client->fill($data)->save();

            return $client->refresh();
        });
    }
}
