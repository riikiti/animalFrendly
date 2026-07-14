<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Events;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Публикуется, когда спор решён в пользу покупателя. Модуль Payment подписывается и ставит
 * в очередь возврат через ЮKassa.
 */
final class OrderRefunded implements DomainEvent
{
    public function __construct(
        public readonly Id $orderId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
