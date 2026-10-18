<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Reclamo;
use App\Models\User;

class ReclamoPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [UserRole::ADMINISTRATIVO, UserRole::REPARTIDOR], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Reclamo $reclamo): bool
    {
        return $this->viewAny($user);
    }
}
