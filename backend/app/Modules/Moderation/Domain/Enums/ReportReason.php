<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Inappropriate = 'inappropriate';
    case Scam = 'scam';
    case Other = 'other';
}
