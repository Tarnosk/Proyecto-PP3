<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case CONFIRMADO = 'confirmado';
    case ANULADO = 'anulado';
}
