<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

enum BreederVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
