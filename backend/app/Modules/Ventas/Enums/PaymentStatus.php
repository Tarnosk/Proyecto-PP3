<?php

namespace App\Modules\Ventas\Enums;

enum PaymentStatus: string
{
    case CONFIRMADO = 'confirmado';
    case ANULADO = 'anulado';
}
