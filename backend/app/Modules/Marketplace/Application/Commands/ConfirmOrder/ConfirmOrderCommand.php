<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\ConfirmOrder;

final class ConfirmOrderCommand
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $actingUserId,
    ) {}
}
