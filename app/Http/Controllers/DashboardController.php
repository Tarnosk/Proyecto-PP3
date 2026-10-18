<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Enums\ClientType;
use App\Enums\UserRole;
use App\Models\Cliente;
use App\Models\Reclamo;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $isAdmin = auth()->user()->rol === UserRole::ADMINISTRATIVO;
        $summary = [
            'totalClientes' => 0,
            'totalMayoristas' => 0,
            'totalMinoristas' => 0,
            'totalReclamos' => 0,
        ];
        $clientes = collect();

        if ($isAdmin) {
            $summary = [
                'totalClientes' => Cliente::query()->count(),
                'totalMayoristas' => Cliente::query()->where('tipo_cliente', ClientType::MAYORISTA)->count(),
                'totalMinoristas' => Cliente::query()->where('tipo_cliente', ClientType::MINORISTA)->count(),
                'totalReclamos' => Reclamo::query()->where('estado', '!=', ClaimStatus::CERRADO)->count(),
            ];
            $clientes = Cliente::query()->latest('updated_at')->limit(3)->get();
        }

        return view('dashboard', [...$summary, 'clientes' => $clientes]);
    }
}
