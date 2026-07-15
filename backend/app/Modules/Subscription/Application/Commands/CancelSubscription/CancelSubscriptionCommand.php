<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Commands\CancelSubscription;

final class CancelSubscriptionCommand
{
    public function __construct(public readonly string $userId) {}
}
