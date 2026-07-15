<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Jobs;

use App\Modules\Payment\Application\Services\ChargeRecurringPaymentService;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ChargeRecurringSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $paymentMethodId,
        public readonly int $amountMinorUnits,
        public readonly string $currency,
        public readonly string $periodLabel,
    ) {}

    public function handle(ChargeRecurringPaymentService $service): void
    {
        $service->charge(
            Id::fromString($this->subscriptionId),
            $this->paymentMethodId,
            Money::fromMinorUnits($this->amountMinorUnits, $this->currency),
            $this->periodLabel,
        );
    }
}
