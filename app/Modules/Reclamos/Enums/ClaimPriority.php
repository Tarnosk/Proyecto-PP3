<?php

namespace App\Modules\Reclamos\Enums;

enum ClaimPriority: string
{
    case BAJA = 'baja';
    case MEDIA = 'media';
    case ALTA = 'alta';
}
