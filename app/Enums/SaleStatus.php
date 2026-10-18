<?php

namespace App\Enums;

enum SaleStatus: string
{
    case PENDIENTE = 'pendiente';
    case CONFIRMADA = 'confirmada';
    case PAGADA = 'pagada';
    case FACTURADA = 'facturada';
    case CANCELADA = 'cancelada';
}
