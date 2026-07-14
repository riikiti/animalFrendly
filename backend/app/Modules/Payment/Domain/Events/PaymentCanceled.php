<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Events;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class PaymentCanceled implements DomainEvent
{
    public function __construct(
        public readonly string $payableType,
        public readonly Id $payableId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
