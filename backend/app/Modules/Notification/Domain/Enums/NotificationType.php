<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Enums;

enum NotificationType: string
{
    case NewMatch = 'new_match';
    case NewMessage = 'new_message';
    case AdoptionApproved = 'adoption_approved';
    case DealCompleted = 'deal_completed';
}
