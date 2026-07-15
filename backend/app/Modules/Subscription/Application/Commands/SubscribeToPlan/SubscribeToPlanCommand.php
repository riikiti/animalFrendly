<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Commands\SubscribeToPlan;

final class SubscribeToPlanCommand
{
    public function __construct(
        public readonly string $planSlug,
        public readonly string $userId,
        public readonly string $returnUrl,
    ) {}
}
