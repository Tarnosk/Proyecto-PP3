<?php

namespace App\Modules\Clientes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Enums\ClientStatus;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Requests\SearchClienteRequest;
use App\Modules\Clientes\Requests\StoreClienteRequest;
use App\Modules\Clientes\Requests\UpdateClienteRequest;
use App\Modules\Reportes\Services\ReportService;
use App\Services\Gateways\ExternalFinanceGateway;
use App\Services\Gateways\RendicionesGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(SearchClienteRequest $request): View
    {
        $search = trim((string) $request->validated('q', ''));
        $clientes = Cliente::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $pattern = '%'.$search.'%';
                    $query->where('nombre', 'like', $pattern)
                        ->orWhere('apellido_razon_social', 'like', $pattern)
                        ->orWhere('dni_cuit', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern)
                        ->orWhere('telefono', 'like', $pattern)
                        ->orWhere('localidad', 'like', $pattern)
                        ->orWhere('direccion', 'like', $pattern);
                });
            })
            ->orderBy('apellido_razon_social')
            ->orderBy('nombre')
            ->limit(100)
            ->get();

        return view('clientes.index', compact('clientes', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', Cliente::class);

        return view('clientes.create');
    }

    public function show(Request $request, Cliente $cliente, ReportService $reports, ExternalFinanceGateway $finance, RendicionesGateway $rendiciones): View
    {
        Gate::authorize('view', $cliente);
        $filters = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        return view('clientes.show', [
            'cliente' => $cliente,
            'saldoDisponible' => $finance->isConfigured('balances'),
            'saldo' => $finance->isConfigured('balances') ? $finance->clientBalance($cliente->id)['saldo'] : null,
            'cobrosDisponibles' => $finance->isConfigured('billing'),
            'cobros' => $finance->isConfigured('billing') ? $finance->clientPayments($cliente->id) : [],
            'cobrosRendidos' => $rendiciones->clientSnapshot($cliente->id),
            'salesReady' => $reports->salesAvailable(),
            'ventas' => $reports->salesAvailable()
                ? $reports->clientSales($cliente, $filters['desde'] ?? null, $filters['hasta'] ?? null)
                : collect(),
            'desde' => $filters['desde'] ?? '',
            'hasta' => $filters['hasta'] ?? '',
        ]);
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        $cliente = new Cliente($request->validated());
        $cliente->estado = ClientStatus::ACTIVO;
        $cliente->creado_por = $request->user()->id;
        $cliente->save();

        return redirect()->route('clientes.create')
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function edit(Cliente $cliente): View
    {
        Gate::authorize('update', $cliente);

        return view('clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }
}
