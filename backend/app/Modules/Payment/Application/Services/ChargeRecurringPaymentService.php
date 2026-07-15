<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Services;

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Domain\Entities\Payment;
use App\Modules\Payment\Domain\Exceptions\YookassaRequestFailedException;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Автосписание за очередной период подписки с ранее сохранённого способа оплаты. Итоговый
 * статус проставляется тем же ProcessWebhookService, что и для обычной оплаты — единая точка
 * истины, см. docs/rules/04-payments-escrow.md.
 */
final class ChargeRecurringPaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly YookassaClientInterface $client,
    ) {}

    public function charge(Id $subscriptionId, string $paymentMethodId, Money $amount, string $periodLabel): void
    {
        $idempotencyKey = "{$subscriptionId->toString()}:renew:{$periodLabel}";

        // Джоба биллинга запускается ежедневно и может попытаться списать за один и тот же
        // просроченный период несколько раз подряд, пока не придёт вебхук — не создаём вторую
        // попытку, пока первая ещё не завершилась.
        if ($this->payments->findByIdempotencyKey($idempotencyKey) !== null) {
            return;
        }

        $payment = Payment::create($this->payments->nextIdentity(), 'subscription', $subscriptionId, $amount, $idempotencyKey);
        $this->payments->save($payment);

        $response = $this->client->chargeWithSavedMethod(
            $amount,
            $paymentMethodId,
            "Автопродление подписки {$subscriptionId->toString()} — AnimalFriendly",
            $idempotencyKey,
        );

        $yookassaPaymentId = $response['id'] ?? null;

        if (! is_string($yookassaPaymentId)) {
            throw YookassaRequestFailedException::create('chargeWithSavedMethod', 'В ответе отсутствует id.');
        }

        $payment->attachYookassaId($yookassaPaymentId);
        $this->payments->save($payment);
    }
}
