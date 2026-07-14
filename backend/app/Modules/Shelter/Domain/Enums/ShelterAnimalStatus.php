<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Enums;

enum ShelterAnimalStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Adopted = 'adopted';
    case Removed = 'removed';
}
