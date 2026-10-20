<?php

namespace App\Modules\Ventas\Enums;

enum PaymentMethod: string
{
    case EFECTIVO = 'efectivo';
    case TRANSFERENCIA = 'transferencia';
    case TARJETA = 'tarjeta';
    case CHEQUE = 'cheque';
    case OTRO = 'otro';
}
