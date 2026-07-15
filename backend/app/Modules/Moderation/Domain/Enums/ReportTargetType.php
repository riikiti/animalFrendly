<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Enums;

enum ReportTargetType: string
{
    case Pet = 'pet';
    case Listing = 'listing';
    case User = 'user';
    case Message = 'message';
}
