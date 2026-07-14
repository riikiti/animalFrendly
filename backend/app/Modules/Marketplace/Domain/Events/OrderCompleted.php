<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Events;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use DateTimeImmutable;

/**
 * Публикуется, когда сделка переходит в completed (оба подтвердили, авто-подтверждение или
 * спор решён в пользу продавца). Модуль Payment подписывается и ставит в очередь выплату
 * продавцу — та же схема, что PetsMatched → Chat, см. docs/plan/03-architecture.md.
 */
final class OrderCompleted implements DomainEvent
{
    public function __construct(
        public readonly Id $orderId,
        public readonly Id $sellerId,
        public readonly Money $payoutAmount,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
