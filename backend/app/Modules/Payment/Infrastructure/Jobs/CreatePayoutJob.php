<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Jobs;

use App\Modules\Payment\Application\Services\CreatePayoutService;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CreatePayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $orderId,
        public readonly string $sellerId,
        public readonly int $payoutAmountMinorUnits,
        public readonly string $currency,
    ) {}

    public function handle(CreatePayoutService $service): void
    {
        $service->createForOrder(
            Id::fromString($this->orderId),
            Id::fromString($this->sellerId),
            Money::fromMinorUnits($this->payoutAmountMinorUnits, $this->currency),
        );
    }
}
