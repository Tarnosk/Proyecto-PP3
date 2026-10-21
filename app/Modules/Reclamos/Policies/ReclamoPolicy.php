<?php

namespace App\Modules\Reclamos\Policies;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use App\Modules\Reclamos\Models\Reclamo;

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
