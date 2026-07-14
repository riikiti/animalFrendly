<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Listeners;

use App\Modules\Marketplace\Domain\Events\OrderRefunded;
use App\Modules\Payment\Infrastructure\Jobs\RefundOrderPaymentJob;

final class DispatchRefundOnOrderRefunded
{
    public function handle(OrderRefunded $event): void
    {
        RefundOrderPaymentJob::dispatch($event->orderId->toString());
    }
}
