<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Http\Requests\Reports\ClientHistoryRequest;
use App\Models\Cliente;
use App\Models\Producto;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function dailySales(Request $request, ReportService $reports): View
    {
        $validated = $request->validate(['fecha' => ['nullable', 'date']]);
        $date = CarbonImmutable::parse($validated['fecha'] ?? today()->toDateString());
        $sales = $reports->dailySales($date);

        return view('reports.daily-sales', [
            'fecha' => $date->toDateString(),
            'ventas' => $sales,
            'totalFacturado' => $sales->sum(fn ($sale) => (float) $sale->total),
        ]);
    }

    public function lowStock(Request $request, ReportService $reports): View
    {
        $validated = $request->validate(['orden' => ['nullable', 'in:asc,desc']]);
        $order = $validated['orden'] ?? 'asc';

        return view('reports.low-stock', [
            'productos' => $reports->lowStock($order),
            'orden' => $order,
        ]);
    }

    public function clientHistory(ClientHistoryRequest $request, ReportService $reports): View
    {
        $data = $request->validated();
        $client = isset($data['cliente_id']) ? Cliente::query()->findOrFail($data['cliente_id']) : null;

        return view('reports.client-history', [
            'clientes' => Cliente::query()->orderBy('apellido_razon_social')->orderBy('nombre')->get(),
            'cliente' => $client,
            'ventas' => $client ? $reports->clientSales($client, $data['desde'] ?? null, $data['hasta'] ?? null) : collect(),
            'desde' => $data['desde'] ?? '',
            'hasta' => $data['hasta'] ?? '',
        ]);
    }

    public function clientProductReach(Request $request): View
    {
        abort_unless(config('services.sales.driver') === 'local', 503, 'El informe requiere un contrato de ventas por cliente y producto con G4.');

        $filters = $request->validate([
            'producto_id' => ['nullable', 'integer', Rule::exists('productos', 'id')],
        ]);
        $producto = isset($filters['producto_id'])
            ? Producto::query()->findOrFail($filters['producto_id'])
            : null;
        $totalClientes = Cliente::query()->count();
        $clientes = null;
        $porcentaje = null;

        if ($producto) {
            $clientes = Cliente::query()
                ->whereHas('ventas', function (Builder $ventas) use ($producto): void {
                    $ventas->whereIn('estado', [
                        SaleStatus::CONFIRMADA->value,
                        SaleStatus::PAGADA->value,
                        SaleStatus::FACTURADA->value,
                    ])->whereHas('detalles', fn (Builder $detalles) => $detalles->where('producto_id', $producto->id));
                })
                ->orderBy('apellido_razon_social')
                ->orderBy('nombre')
                ->paginate(20)
                ->withQueryString();

            $porcentaje = $totalClientes > 0
                ? round($clientes->total() * 100 / $totalClientes, 2)
                : 0.0;
        }

        return view('reports.client-product-reach', [
            'productos' => Producto::query()->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'producto' => $producto,
            'clientes' => $clientes,
            'totalClientes' => $totalClientes,
            'porcentaje' => $porcentaje,
        ]);
    }
}
