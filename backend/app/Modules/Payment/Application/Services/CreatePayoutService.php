<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Services;

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Domain\Entities\Payout;
use App\Modules\Payment\Domain\Repositories\PayoutRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Вызывается из CreatePayoutJob (после Marketplace\Domain\Events\OrderCompleted). Идемпотентна
 * по order_id. По умолчанию payouts_enabled=false — выплата остаётся pending для ручной
 * обработки, см. docs/rules/04-payments-escrow.md ("ошибка выплаты не блокирует completed").
 */
final class CreatePayoutService
{
    public function __construct(
        private readonly PayoutRepositoryInterface $payouts,
        private readonly YookassaClientInterface $client,
    ) {}

    public function createForOrder(Id $orderId, Id $sellerId, Money $payoutAmount): void
    {
        if ($this->payouts->findByOrderId($orderId) !== null) {
            return;
        }

        $payout = Payout::create($this->payouts->nextIdentity(), $orderId, $sellerId, $payoutAmount);
        $this->payouts->save($payout);

        if (! (bool) config('yookassa.payouts_enabled')) {
            return;
        }

        $payout->markProcessing();
        $this->payouts->save($payout);

        try {
            $response = $this->client->createPayout($payoutAmount, "{$orderId->toString()}:payout");
            $yookassaPayoutId = $response['id'] ?? null;

            if (! is_string($yookassaPayoutId)) {
                throw new \RuntimeException('В ответе ЮKassa отсутствует id выплаты.');
            }

            $payout->markPaid($yookassaPayoutId);
        } catch (Throwable $e) {
            $payout->markFailed();
            Log::error('yookassa.payout.failed', ['order_id' => $orderId->toString(), 'error' => $e->getMessage()]);
        }

        $this->payouts->save($payout);
    }
}
