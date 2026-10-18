<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\Reclamo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'cliente_id' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', Rule::enum(ClaimStatus::class)],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $claims = Reclamo::query()
            ->when(isset($filters['cliente_id']), fn ($query) => $query->where('cliente_id', $filters['cliente_id']))
            ->when(isset($filters['estado']), fn ($query) => $query->where('estado', $filters['estado']))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20);

        return response()->json([
            'data' => $claims->getCollection()->map(fn (Reclamo $claim) => $this->claimData($claim)),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
            ],
        ]);
    }

    public function show(Reclamo $reclamo): JsonResponse
    {
        return response()->json(['data' => $this->claimData($reclamo)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->where('estado', 'activo')],
            'usuario_id' => ['required', 'integer', Rule::exists('usuarios', 'id')->where('estado', 'activo')->whereIn('rol', ['administrativo', 'repartidor'])],
            'asunto' => ['required', 'string', 'max:160'],
            'descripcion' => ['required', 'string'],
            'prioridad' => ['required', 'in:baja,media,alta'],
        ]);

        $claim = new Reclamo($data);
        $claim->usuario_id = $data['usuario_id'];
        $claim->estado = ClaimStatus::ABIERTO;
        $claim->save();

        return response()->json(['data' => $this->claimData($claim)], 201);
    }

    public function updateStatus(Request $request, Reclamo $reclamo): JsonResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::enum(ClaimStatus::class)],
        ]);
        $reclamo->estado = $data['estado'];
        $reclamo->save();

        return response()->json(['data' => $this->claimData($reclamo)]);
    }

    private function claimData(Reclamo $claim): array
    {
        return [
            'id' => $claim->id,
            'cliente_id' => $claim->cliente_id,
            'usuario_id' => $claim->usuario_id,
            'asunto' => $claim->asunto,
            'descripcion' => $claim->descripcion,
            'prioridad' => $claim->prioridad->value,
            'estado' => $claim->estado->value,
            'created_at' => $claim->created_at->toIso8601String(),
            'updated_at' => $claim->updated_at->toIso8601String(),
        ];
    }
}
