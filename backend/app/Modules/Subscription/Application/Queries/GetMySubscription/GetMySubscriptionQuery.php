<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Queries\GetMySubscription;

final class GetMySubscriptionQuery
{
    public function __construct(public readonly string $userId) {}
}
