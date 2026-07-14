<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Events;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Публикуется, когда приют одобряет заявку на усыновление. Модуль Chat подписывается и
 * создаёт беседу между заявителем и приютом — та же схема, что и PetsMatched, см.
 * docs/plan/03-architecture.md.
 */
final class AdoptionRequestApproved implements DomainEvent
{
    public function __construct(
        public readonly Id $adoptionRequestId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
