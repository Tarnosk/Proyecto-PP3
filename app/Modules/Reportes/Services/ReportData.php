<?php

namespace App\Modules\Reportes\Services;

use App\Modules\Reportes\Enums\SaleStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReportData
{
    public function sales(array $rows): Collection
    {
        abort_unless(array_is_list($rows), 502, 'Ventas devolvio una lista invalida.');
        $this->validate($rows, [
            '*' => ['array'],
            '*.id' => ['required', 'integer', 'min:1', 'distinct'],
            '*.fecha' => ['required', 'date'],
            '*.numero_comprobante' => ['nullable', 'string'],
            '*.total' => ['required', 'numeric'],
            '*.estado' => ['required', 'array'],
            '*.estado.valor' => ['required', Rule::enum(SaleStatus::class)],
            '*.cliente' => ['required', 'array'],
            '*.cliente.id' => ['required', 'integer', 'min:1'],
            '*.cliente.nombre' => ['required', 'string'],
            '*.detalles' => ['present', 'array'],
            '*.detalles.*' => ['array'],
            '*.detalles.*.cantidad' => ['required', 'integer'],
            '*.detalles.*.subtotal' => ['required', 'numeric'],
            '*.detalles.*.producto' => ['required', 'string'],
        ]);

        return collect($rows)->map(fn (array $row): object => (object) [
            'id' => (int) $row['id'],
            'fecha' => CarbonImmutable::parse($row['fecha']),
            'numero_factura' => $row['numero_comprobante'] ?? null,
            'total' => (float) $row['total'],
            'estado' => SaleStatus::from($row['estado']['valor']),
            'cliente' => (object) [...$row['cliente'], 'id' => (int) $row['cliente']['id'], 'apellido_razon_social' => ''],
            'detalles' => collect($row['detalles'] ?? [])->map(fn (array $detail): object => (object) [
                'cantidad' => (int) $detail['cantidad'],
                'subtotal' => (float) $detail['subtotal'],
                'producto' => (object) ['nombre' => $detail['producto']],
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
