<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Реальных ключей ЮKassa нет — подменяет только HTTP-уровень. Вся остальная цепочка
 * (InitiatePaymentService, YookassaPaymentGateway, вебхук, Job) в тестах остаётся настоящей.
 */
final class FakeYookassaClient implements YookassaClientInterface
{
    public function createPayment(
        Money $amount,
        string $description,
        string $returnUrl,
        string $idempotencyKey,
        bool $savePaymentMethod = false,
    ): array {
        return [
            'id' => 'yk-payment-'.$idempotencyKey,
            'confirmation' => ['confirmation_url' => 'https://yookassa.ru/pay/test'],
            ...($savePaymentMethod ? ['payment_method' => ['id' => 'yk-pm-'.$idempotencyKey, 'saved' => true]] : []),
        ];
    }

    public function chargeWithSavedMethod(
        Money $amount,
        string $paymentMethodId,
        string $description,
        string $idempotencyKey,
    ): array {
        return ['id' => 'yk-payment-'.$idempotencyKey, 'status' => 'pending'];
    }

    public function createRefund(string $yookassaPaymentId, Money $amount, string $idempotencyKey): array
    {
        return ['id' => 'yk-refund-'.$idempotencyKey];
    }

    public function createPayout(Money $amount, string $idempotencyKey): array
    {
        return ['id' => 'yk-payout-'.$idempotencyKey];
    }
}
