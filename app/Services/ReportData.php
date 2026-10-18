<?php

namespace App\Services;

use App\Enums\SaleStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class ReportData
{
    public function sales(array $rows): Collection
    {
        $this->validate($rows, [
            '*' => ['array'],
            '*.fecha' => ['required', 'date'],
            '*.numero_factura' => ['nullable', 'string'],
            '*.total' => ['required', 'numeric'],
            '*.estado' => ['required', 'in:pendiente,confirmada,pagada,facturada,cancelada'],
            '*.cliente' => ['required', 'array'],
            '*.cliente.nombre' => ['required', 'string'],
            '*.cliente.apellido_razon_social' => ['required', 'string'],
            '*.detalles' => ['sometimes', 'array'],
            '*.detalles.*' => ['array'],
            '*.detalles.*.cantidad' => ['required', 'integer'],
            '*.detalles.*.subtotal' => ['required', 'numeric'],
            '*.detalles.*.producto' => ['required', 'array'],
            '*.detalles.*.producto.nombre' => ['required', 'string'],
        ]);

        return collect($rows)->map(fn (array $row): object => (object) [
            'fecha' => CarbonImmutable::parse($row['fecha']),
            'numero_factura' => $row['numero_factura'] ?? null,
            'total' => (float) $row['total'],
            'estado' => SaleStatus::from($row['estado']),
            'cliente' => (object) $row['cliente'],
            'detalles' => collect($row['detalles'] ?? [])->map(fn (array $detail): object => (object) [
                'cantidad' => (int) $detail['cantidad'],
                'subtotal' => (float) $detail['subtotal'],
                'producto' => (object) $detail['producto'],
            ]),
        ]);
    }

    private function validate(array $rows, array $rules): void
    {
        if (Validator::make($rows, $rules)->fails()) {
            abort(502, 'El servicio externo devolvio datos invalidos.');
        }
    }
}
