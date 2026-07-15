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
    /**
     * @param  array<string, mixed>|null  $rawPayload  сырой ответ ЮKassa (object из вебхука) —
     *                                                 используется слушателем Subscription,
     *                                                 чтобы достать payment_method.id/saved.
     */
    public function __construct(
        public readonly string $payableType,
        public readonly Id $payableId,
        public readonly Money $amount,
        private readonly DateTimeImmutable $occurredAt,
        public readonly ?array $rawPayload = null,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
