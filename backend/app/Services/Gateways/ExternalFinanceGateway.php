<?php

namespace App\Services\Gateways;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ExternalFinanceGateway
{
    public function __construct(private ExternalJsonClient $client) {}

    public function isConfigured(string $service): bool
    {
        return $this->client->isConfigured($service);
    }

    public function clientBalance(int $clientId): array
    {
        $path = str_replace('{id}', (string) $clientId, config('services.balances.client_path'));
        $row = $this->client->get('balances', $path);
        $this->validate($row, [
            'cliente_id' => ['required', 'integer'],
            'saldo' => ['required', 'numeric', 'min:0'],
            'moneda' => ['required', 'in:ARS'],
        ]);

        if ((int) $row['cliente_id'] !== $clientId) {
            abort(502, 'El servicio de saldos devolvio otro cliente.');
        }

        return $row;
    }

    public function clientPayments(int $clientId): array
    {
        $rows = $this->client->get('billing', config('services.billing.payments_path'), ['cliente_id' => $clientId]);
        $this->requireList($rows);
        $this->validate($rows, [
            '*' => ['array'],
            '*.id' => ['required', 'integer'],
            '*.cliente_id' => ['required', 'integer'],
            '*.fecha' => ['required', 'date'],
            '*.monto_total' => ['required', 'numeric', 'min:0'],
            '*.medio_pago' => ['required', 'string'],
            '*.comprobante_nro' => ['nullable', 'string'],
            '*.observaciones' => ['nullable', 'string'],
        ]);

        if (collect($rows)->contains(fn ($row) => (int) $row['cliente_id'] !== $clientId)) {
            abort(502, 'El servicio de cobros devolvio otro cliente.');
        }

        return collect($rows)->sortByDesc(fn (array $row) => Carbon::parse($row['fecha'])->timestamp)->values()->all();
    }

    public function debtorBalances(): array
    {
        $rows = $this->client->get('balances', config('services.balances.debtors_path'));
        $this->requireList($rows);
        $this->validate($rows, [
            '*' => ['array'],
            '*.cliente_id' => ['required', 'integer', 'min:1', 'distinct'],
            '*.saldo' => ['required', 'numeric', 'min:0.01'],
        ]);

        return $rows;
    }

    public function pendingInvoices(): array
    {
        $rows = $this->client->get('billing', config('services.billing.invoices_path'));
        $this->requireList($rows);
        $this->validate($rows, [
            '*' => ['array'],
            '*.id' => ['required', 'integer'],
            '*.cliente_id' => ['required', 'integer', 'min:1'],
            '*.numero' => ['required', 'string'],
            '*.vencimiento' => ['required', 'date'],
            '*.importe_pendiente' => ['required', 'numeric', 'min:0.01'],
        ]);

        return $rows;
    }

    public function productRanking(?string $from, ?string $to): array
    {
        $rows = $this->client->get('sales', config('services.sales.ranking_path'), array_filter([
            'desde' => $from,
            'hasta' => $to,
        ], fn ($value) => $value !== null));
        $this->requireList($rows);
        $this->validate($rows, [
            '*' => ['array'],
            '*.producto_id' => ['required', 'integer', 'min:1'],
            '*.producto_nombre' => ['required', 'string'],
            '*.cantidad_vendida' => ['required', 'integer', 'min:0'],
            '*.monto_facturado' => ['required', 'numeric', 'min:0'],
        ]);

        return $rows;
    }

    private function requireList(array $rows): void
    {
        if (! array_is_list($rows)) {
            abort(502, 'El servicio externo devolvio una lista invalida.');
        }
    }

    private function validate(array $rows, array $rules): void
    {
        if (Validator::make($rows, $rules)->fails()) {
            abort(502, 'El servicio externo devolvio datos invalidos.');
        }
    }
}
