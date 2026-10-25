<?php

namespace App\Modules\Reportes\Enums;

/** Estados leídos del contrato de Ventas; G1 no define sus transiciones. */
enum SaleStatus: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Preparada = 'preparada';
    case Entregada = 'entregada';
    case Pagada = 'pagada';
    case Facturada = 'facturada';
    case Cancelada = 'cancelada';
}
