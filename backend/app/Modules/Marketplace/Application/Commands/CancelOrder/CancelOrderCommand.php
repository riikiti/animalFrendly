<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\CancelOrder;

final class CancelOrderCommand
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $actingUserId,
    ) {}
}
