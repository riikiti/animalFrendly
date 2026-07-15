<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Enums;

enum SubscriptionStatus: string
{
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Canceled = 'canceled';
    case Expired = 'expired';
    case PastDue = 'past_due';
}
