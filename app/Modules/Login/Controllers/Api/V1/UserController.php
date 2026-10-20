<?php

namespace App\Modules\Login\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Login\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __invoke(User $user): JsonResponse
    {
        return response()->json(['data' => [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'rol' => $user->rol->value,
            'estado' => $user->estado->value,
        ]]);
    }
}
