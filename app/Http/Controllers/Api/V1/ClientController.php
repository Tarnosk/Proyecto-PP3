<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ClientStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClientWriteRequest;
use App\Http\Resources\ClientResource;
use App\Models\Cliente;
use App\Services\Gateways\ExternalFinanceGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:160'],
            'estado' => ['nullable', 'in:activo,inactivo'],
            'tipo' => ['nullable', 'in:minorista,mayorista'],
            'zona_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $search = trim($filters['buscar'] ?? '');
        $clients = Cliente::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $pattern = '%'.$search.'%';
                    $query->where('nombre', 'like', $pattern)
                        ->orWhere('apellido_razon_social', 'like', $pattern)
                        ->orWhere('dni_cuit', 'like', $pattern);
                });
            })
            ->when(isset($filters['estado']), fn ($query) => $query->where('estado', $filters['estado']))
            ->when(isset($filters['tipo']), fn ($query) => $query->where('tipo_cliente', $filters['tipo']))
            ->when(isset($filters['zona_id']), fn ($query) => $query->where('zona_id', $filters['zona_id']))
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return response()->json([
            'data' => $clients->getCollection()->map(fn (Cliente $client) => (new ClientResource($client))->toArray($request)),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
        ]);
    }

    public function show(Request $request, Cliente $cliente): JsonResponse
    {
        return response()->json(['data' => (new ClientResource($cliente))->toArray($request)]);
    }

    public function store(ClientWriteRequest $request): JsonResponse
    {
        $client = new Cliente($request->validated());
        $client->estado = ClientStatus::ACTIVO;
        $client->creado_por = $request->user()->id;
        $client->save();
        $client->refresh();

        return response()->json(['data' => (new ClientResource($client))->toArray($request)], 201)
            ->header('Location', url('/api/v1/clientes/'.$client->id));
    }

    public function replace(ClientWriteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());

        return response()->json(['data' => (new ClientResource($cliente))->toArray($request)]);
    }

    public function patch(ClientWriteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());

        return response()->json(['data' => (new ClientResource($cliente))->toArray($request)]);
    }

    public function balance(Cliente $cliente, ExternalFinanceGateway $finance): JsonResponse
    {
        return response()->json(['data' => $finance->clientBalance($cliente->id)]);
    }

    public function payments(Cliente $cliente, ExternalFinanceGateway $finance): JsonResponse
    {
        return response()->json(['data' => $finance->clientPayments($cliente->id)]);
    }
}
