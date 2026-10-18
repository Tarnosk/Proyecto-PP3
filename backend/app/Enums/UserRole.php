<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMINISTRATIVO = 'administrativo';
    case REPARTIDOR = 'repartidor';
    case CONTADOR = 'contador';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
