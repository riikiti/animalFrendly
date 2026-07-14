<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Services;

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Domain\Enums\PaymentStatus;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\Log;

/**
 * Вызывается из RefundOrderPaymentJob (после Marketplace\Domain\Events\OrderRefunded).
 * Финальный статус payments.status=refunded проставляется вебхуком refund.succeeded, не
 * оптимистично здесь — единый источник истины, см. docs/rules/04-payments-escrow.md.
 */
final class RefundOrderPaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly YookassaClientInterface $client,
    ) {}

    public function refundForOrder(Id $orderId): void
    {
        $payment = $this->payments->findByPayable('order', $orderId);

        if ($payment === null || $payment->status() !== PaymentStatus::Succeeded) {
            Log::warning('yookassa.refund.no_succeeded_payment', ['order_id' => $orderId->toString()]);

            return;
        }

        $yookassaPaymentId = $payment->yookassaPaymentId();

        if ($yookassaPaymentId === null) {
            Log::warning('yookassa.refund.missing_yookassa_id', ['order_id' => $orderId->toString()]);

            return;
        }

        $this->client->createRefund($yookassaPaymentId, $payment->amount(), "{$orderId->toString()}:refund");
    }
}
