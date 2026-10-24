<?php

namespace App\Modules\Shared\Auth;

use App\Modules\Login\Enums\UserRole;

/** Adapta la identidad de G1 al vocabulario de G2, sin duplicar usuarios. */
class G2Identity
{
    public static function id(): int
    {
        abort_unless(auth()->check(), 401);

        return (int) auth()->user()->getAuthIdentifier();
    }

    public static function role(): string
    {
        self::id();
        $role = auth()->user()->rol;
        $role = $role instanceof UserRole ? $role->value : $role;

        return $role === 'administrativo' ? 'admin' : (string) $role;
    }

    public static function assertOwner(?array $record, string $key = 'id_repartidor'): void
    {
        abort_unless($record !== null && (self::role() === 'admin'
            || (int) ($record[$key] ?? 0) === self::id()), 404);
    }
}
