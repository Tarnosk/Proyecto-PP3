<?php

namespace App\Modules\Reportes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Reportes\Requests\ClientHistoryRequest;
use App\Modules\Reportes\Services\ReportService;
use App\Services\Gateways\ExternalCatalogGateway;
use App\Services\Gateways\ExternalFinanceGateway;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function clientHistory(ClientHistoryRequest $request, ReportService $reports): View
    {
        $data = $request->validated();
        $client = isset($data['cliente_id']) ? Cliente::query()->findOrFail($data['cliente_id']) : null;

        return view('reports.client-history', [
            'clientes' => Cliente::query()->orderBy('apellido_razon_social')->orderBy('nombre')->get(),
            'cliente' => $client,
            'salesReady' => $reports->salesAvailable(),
            'ventas' => $client && $reports->salesAvailable()
                ? $reports->clientSales($client, $data['desde'] ?? null, $data['hasta'] ?? null)
                : collect(),
            'desde' => $data['desde'] ?? '',
            'hasta' => $data['hasta'] ?? '',
        ]);
    }

    public function clientProductReach(Request $request, ExternalCatalogGateway $catalog, ReportService $reports): View
    {
        $filters = $request->validate([
            'producto_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $ready = $catalog->isConfigured() && $reports->salesAvailable();
        $productos = $ready ? $catalog->products() : collect();
        $producto = isset($filters['producto_id'])
            ? $productos->firstWhere('id', (int) $filters['producto_id'])
            : null;
        if ($ready && isset($filters['producto_id'])) {
            abort_unless($producto, 404);
        }
        $totalClientes = Cliente::query()->count();
        $clientes = null;
        $porcentaje = null;

        if ($ready && $producto) {
            $buyerIds = $reports->productBuyerIds($producto->id);
            $clientes = Cliente::query()
                ->whereIn('id', $buyerIds)
                ->orderBy('apellido_razon_social')
                ->orderBy('nombre')
                ->paginate(20)
                ->withQueryString();

            $porcentaje = $totalClientes > 0
                ? round($clientes->total() * 100 / $totalClientes, 2)
                : 0.0;
        }

        return view('reports.client-product-reach', [
            'ready' => $ready,
            'productos' => $productos,
            'producto' => $producto,
            'clientes' => $clientes,
            'totalClientes' => $totalClientes,
            'porcentaje' => $porcentaje,
        ]);
    }

    public function debtors(Request $request, ExternalFinanceGateway $finance): View
    {
        $filters = $request->validate([
            'orden' => ['nullable', 'in:mayor,menor'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $ready = $finance->isConfigured('balances') && $finance->isConfigured('billing');
        $debtors = collect();

        if ($ready) {
            $balances = collect($finance->debtorBalances())->keyBy('cliente_id');
            $clients = Cliente::query()->whereIn('id', $balances->keys())->get()->keyBy('id');
            $invoices = collect($finance->pendingInvoices())->groupBy('cliente_id');
            $debtors = $balances->map(function (array $balance) use ($clients, $invoices): ?array {
                $id = (int) $balance['cliente_id'];
                $client = $clients->get($id);
                if (! $client) {
                    return null;
                }

                return [
                    'cliente' => $client,
                    'saldo' => $balance['saldo'],
                    'facturas' => $invoices->get($id, collect()),
                ];
            })->filter()->sortBy('saldo', SORT_NUMERIC, ($filters['orden'] ?? 'mayor') === 'mayor')->values();
        }

        $page = (int) ($filters['page'] ?? 1);
        $debtors = new LengthAwarePaginator($debtors->forPage($page, 20), $debtors->count(), 20, $page, [
            'path' => route('reports.debtors'), 'query' => $request->query(),
        ]);

        return view('reports.debtors', [
            'ready' => $ready,
            'debtors' => $debtors,
            'orden' => $filters['orden'] ?? 'mayor',
        ]);
    }

    public function productRanking(Request $request, ExternalFinanceGateway $finance): View
    {
        $filters = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $ready = $finance->isConfigured('sales');
        $ranking = $ready
            ? collect($finance->productRanking($filters['desde'] ?? null, $filters['hasta'] ?? null))
                ->sortByDesc('cantidad_vendida')->values()
            : collect();

        $page = (int) ($filters['page'] ?? 1);
        $ranking = new LengthAwarePaginator($ranking->forPage($page, 20), $ranking->count(), 20, $page, [
            'path' => route('reports.product-ranking'), 'query' => $request->query(),
        ]);

        return view('reports.product-ranking', [
            'ready' => $ready,
            'ranking' => $ranking,
            'desde' => $filters['desde'] ?? '',
            'hasta' => $filters['hasta'] ?? '',
        ]);
    }
}
