<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Jobs;

use App\Modules\Payment\Application\Services\RefundOrderPaymentService;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RefundOrderPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $orderId) {}

    public function handle(RefundOrderPaymentService $service): void
    {
        $service->refundForOrder(Id::fromString($this->orderId));
    }
}
