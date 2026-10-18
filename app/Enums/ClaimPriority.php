<?php

namespace App\Enums;

enum ClaimPriority: string
{
    case BAJA = 'baja';
    case MEDIA = 'media';
    case ALTA = 'alta';
}
