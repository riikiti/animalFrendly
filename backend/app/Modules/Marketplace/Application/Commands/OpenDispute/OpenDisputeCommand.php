<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\OpenDispute;

final class OpenDisputeCommand
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $actingUserId,
        public readonly string $reason,
    ) {}
}
