<?php

namespace App\Modules\Login\Enums;

enum UserStatus: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
}
