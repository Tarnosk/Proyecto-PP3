<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiTokenService
{
    public function issue(User $user, Request $request): array
    {
        $expiresAt = now()->addMinutes((int) config('session.lifetime', 120));
        $token = Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'expires_at' => $expiresAt->timestamp,
            'nonce' => Str::random(64),
        ], JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($user, $request, $token): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->forceFill(['current_session_id' => hash('sha256', $token)])->save();
            $lockedUser->loginLogs()->create([
                'fecha_hora' => now(),
                'ip' => $request->ip(),
            ]);
        });

        return ['token' => $token, 'expires_at' => $expiresAt->toIso8601String()];
    }

    public function resolve(?string $token): ?User
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (! is_array($payload)
            || ! isset($payload['user_id'], $payload['expires_at'])
            || ! is_int($payload['user_id'])
            || ! is_int($payload['expires_at'])
            || $payload['expires_at'] <= now()->timestamp) {
            return null;
        }

        return User::query()
            ->whereKey($payload['user_id'])
            ->where('estado', UserStatus::ACTIVO->value)
            ->where('current_session_id', hash('sha256', $token))
            ->first();
    }

    public function revoke(User $user, string $token): void
    {
        $user->newQuery()
            ->whereKey($user->id)
            ->where('current_session_id', hash('sha256', $token))
            ->update(['current_session_id' => null]);
    }
}
