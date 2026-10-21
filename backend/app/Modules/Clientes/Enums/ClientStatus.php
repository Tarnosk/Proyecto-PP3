<?php

namespace App\Modules\Clientes\Enums;

enum ClientStatus: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
}
