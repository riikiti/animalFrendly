<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Contracts;

use App\Shared\Domain\ValueObjects\Money;

/**
 * Реализация — Payment\Infrastructure\External\YookassaClient (Http-фасад). Интерфейс живёт
 * в Application, чтобы Application-сервисы можно было тестировать с моком, без реальной сети.
 */
interface YookassaClientInterface
{
    /**
     * @return array<string, mixed> сырой ответ ЮKassa API v3 (создание платежа)
     */
    public function createPayment(Money $amount, string $description, string $returnUrl, string $idempotencyKey): array;

    /**
     * @return array<string, mixed>
     */
    public function createRefund(string $yookassaPaymentId, Money $amount, string $idempotencyKey): array;

    /**
     * @return array<string, mixed>
     */
    public function createPayout(Money $amount, string $idempotencyKey): array;
}
