<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case EFECTIVO = 'efectivo';
    case TRANSFERENCIA = 'transferencia';
    case TARJETA = 'tarjeta';
    case CHEQUE = 'cheque';
    case OTRO = 'otro';
}
