<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case ABIERTO = 'abierto';
    case EN_PROCESO = 'en_proceso';
    case CERRADO = 'cerrado';
}
