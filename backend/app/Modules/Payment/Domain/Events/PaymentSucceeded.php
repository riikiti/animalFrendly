<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Events;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use DateTimeImmutable;

/**
 * Публикуется, когда платёж по вебхуку ЮKassa подтверждён (payment.succeeded). Модуль
 * Marketplace подписывается и переводит соответствующий заказ в paid_escrow — та же схема,
 * что PetsMatched → Chat, см. docs/plan/03-architecture.md.
 */
final class PaymentSucceeded implements DomainEvent
{
    public function __construct(
        public readonly string $payableType,
        public readonly Id $payableId,
        public readonly Money $amount,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
