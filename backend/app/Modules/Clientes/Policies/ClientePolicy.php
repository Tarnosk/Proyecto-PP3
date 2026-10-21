<?php

namespace App\Modules\Clientes\Policies;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [UserRole::ADMINISTRATIVO, UserRole::REPARTIDOR], true);
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->rol === UserRole::ADMINISTRATIVO;
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->rol === UserRole::ADMINISTRATIVO;
    }
}
