<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Events;

use App\Modules\Marketplace\Domain\Enums\ListingStatus;
use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Публикуется при любой смене статуса листинга. Модуль Search подписывается и переиндексирует
 * (или удаляет из индекса, если статус больше не published) документ листинга.
 */
final class ListingStatusChanged implements DomainEvent
{
    public function __construct(
        public readonly Id $listingId,
        public readonly ListingStatus $status,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
