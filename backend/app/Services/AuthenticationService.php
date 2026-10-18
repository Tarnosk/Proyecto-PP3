<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public const MAX_FAILED_ATTEMPTS = 3;

    public const LOCK_MINUTES = 15;

    public function authenticate(string $username, string $password): User
    {
        $result = DB::transaction(function () use ($username, $password): array {
            $user = User::query()
                ->where('usuario', $username)
                ->lockForUpdate()
                ->first();

            if (! $user || $user->estado !== UserStatus::ACTIVO) {
                return ['user' => null, 'message' => 'Las credenciales ingresadas son incorrectas.'];
            }

            if ($user->bloqueado_hasta?->isFuture()) {
                return ['user' => null, 'message' => 'El usuario está bloqueado temporalmente. Intente nuevamente más tarde.'];
            }

            if (! Hash::check($password, $user->password_hash)) {
                $attempts = $user->intentos_fallidos + 1;
                $locked = $attempts >= self::MAX_FAILED_ATTEMPTS;

                $user->forceFill([
                    'intentos_fallidos' => $attempts,
                    'ultimo_intento_fallido' => now(),
                    'bloqueado_hasta' => $locked ? now()->addMinutes(self::LOCK_MINUTES) : null,
                ])->save();

                $message = $locked
                    ? 'Demasiados intentos fallidos. Usuario bloqueado por '.self::LOCK_MINUTES.' minutos.'
                    : 'Las credenciales ingresadas son incorrectas. Intentos restantes: '.(self::MAX_FAILED_ATTEMPTS - $attempts).'.';

                return ['user' => null, 'message' => $message];
            }

            $user->forceFill([
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
                'ultimo_intento_fallido' => null,
            ])->save();

            return ['user' => $user, 'message' => null];
        }, 3);

        if (! $result['user']) {
            throw ValidationException::withMessages(['usuario' => $result['message']]);
        }

        return $result['user'];
    }

    public function startSession(User $user, Request $request): void
    {
        $plainToken = Str::random(64);

        DB::transaction(function () use ($user, $request, $plainToken): void {
            $user->forceFill([
                'current_session_id' => hash('sha256', $plainToken),
            ])->save();

            $user->loginLogs()->create([
                'fecha_hora' => now(),
                'ip' => $request->ip(),
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('session_token', $plainToken);
        $request->session()->put('last_activity', now()->timestamp);
    }

    public function endSession(Request $request): void
    {
        $user = $request->user();
        $plainToken = (string) $request->session()->get('session_token', '');

        if ($user && $plainToken !== '') {
            $user->newQuery()
                ->whereKey($user->getKey())
                ->where('current_session_id', hash('sha256', $plainToken))
                ->update(['current_session_id' => null]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
