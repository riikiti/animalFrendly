<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Enums;

enum AdoptionRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
