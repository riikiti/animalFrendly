<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Enums;

enum PetStatus: string
{
    case Active = 'active';
    case Hidden = 'hidden';
    case Archived = 'archived';
}
