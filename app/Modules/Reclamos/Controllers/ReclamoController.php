<?php

namespace App\Modules\Reclamos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Enums\ClientStatus;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Reclamos\Enums\ClaimStatus;
use App\Modules\Reclamos\Models\Reclamo;
use App\Modules\Reclamos\Requests\StoreReclamoRequest;
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
