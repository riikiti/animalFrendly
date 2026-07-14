<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Enums;

enum ShelterVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
