<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Commands\SubscribeToPlan;

use App\Modules\Subscription\Domain\Entities\Subscription;

final class SubscribeToPlanResult
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly string $confirmationUrl,
    ) {}
}
