<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Listeners;

use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Payment\Infrastructure\Jobs\CreatePayoutJob;

final class DispatchPayoutOnOrderCompleted
{
    public function handle(OrderCompleted $event): void
    {
        CreatePayoutJob::dispatch(
            $event->orderId->toString(),
            $event->sellerId->toString(),
            $event->payoutAmount->minorUnits,
            $event->payoutAmount->currency,
        );
    }
}
