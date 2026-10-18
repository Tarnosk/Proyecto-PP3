<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiTokenService;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, AuthenticationService $authentication, ApiTokenService $tokens): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');
        $data = $request->validate([
            'usuario' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = $authentication->authenticate(trim($data['usuario']), $data['password']);
        $issued = $tokens->issue($user, $request);

        return response()->json([
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $issued['token'],
                'expires_at' => $issued['expires_at'],
                'usuario' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'rol' => $user->rol->value,
                ],
            ],
        ]);
    }

    public function logout(Request $request, ApiTokenService $tokens): JsonResponse
    {
        $tokens->revoke($request->user(), $request->bearerToken());

        return response()->json(['message' => 'Sesion cerrada.']);
    }
}
