<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Enums;

enum BillingPeriod: string
{
    case Month = 'month';
    case Year = 'year';
}
