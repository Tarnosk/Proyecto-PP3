<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Enums\ClientStatus;
use App\Http\Requests\Reclamos\StoreReclamoRequest;
use App\Models\Cliente;
use App\Models\Reclamo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReclamoController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Reclamo::class);

        $reclamos = Reclamo::query()
            ->with('cliente')
            ->latest('created_at')
            ->limit(100)
            ->get();

        return view('reclamos.index', compact('reclamos'));
    }

    public function create(): View
    {
        Gate::authorize('create', Reclamo::class);

        $clientes = Cliente::query()
            ->where('estado', ClientStatus::ACTIVO)
            ->orderBy('apellido_razon_social')
            ->orderBy('nombre')
            ->get();

        return view('reclamos.create', compact('clientes'));
    }

    public function store(StoreReclamoRequest $request): RedirectResponse
    {
        $reclamo = new Reclamo($request->validated());
        $reclamo->usuario_id = $request->user()->id;
        $reclamo->estado = ClaimStatus::ABIERTO;
        $reclamo->save();

        return redirect()->route('reclamos.index')
            ->with('success', 'Reclamo creado correctamente.');
    }
}
