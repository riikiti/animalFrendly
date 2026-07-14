<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum AccountType: string
{
    case Owner = 'owner';
    case Breeder = 'breeder';
    case Shelter = 'shelter';
    case Admin = 'admin';
    case Moderator = 'moderator';
}
