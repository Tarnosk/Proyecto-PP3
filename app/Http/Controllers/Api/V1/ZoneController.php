<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use Illuminate\Http\JsonResponse;

class ZoneController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => Zona::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion'])]);
    }
}
