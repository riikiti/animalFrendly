<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Services;

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Domain\Entities\Payment;
use App\Modules\Payment\Domain\Exceptions\YookassaRequestFailedException;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class InitiatePaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly YookassaClientInterface $client,
    ) {}

    public function initiate(
        string $payableType,
        Id $payableId,
        Money $amount,
        string $returnUrl,
        bool $savePaymentMethod = false,
    ): InitiatePaymentResult {
        $idempotencyKey = "{$payableId->toString()}:create";

        $payment = Payment::create($this->payments->nextIdentity(), $payableType, $payableId, $amount, $idempotencyKey);
        $this->payments->save($payment);

        $response = $this->client->createPayment(
            $amount,
            "Оплата заказа {$payableId->toString()} — AnimalFriendly",
            $returnUrl,
            $idempotencyKey,
            $savePaymentMethod,
        );

        $yookassaPaymentId = $response['id'] ?? null;
        $confirmationUrl = $response['confirmation']['confirmation_url'] ?? null;

        if (! is_string($yookassaPaymentId) || ! is_string($confirmationUrl)) {
            throw YookassaRequestFailedException::create('createPayment', 'В ответе отсутствуют id или confirmation_url.');
        }

        $payment->attachYookassaId($yookassaPaymentId);
        $this->payments->save($payment);

        return new InitiatePaymentResult($confirmationUrl, $yookassaPaymentId);
    }
}
