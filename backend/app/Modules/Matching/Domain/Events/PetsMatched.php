<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Events;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Публикуется при взаимном лайке. Модуль Chat подписывается на это событие и создаёт
 * беседу — межмодульная связь через событие, а не прямая зависимость,
 * см. docs/plan/03-architecture.md.
 */
final class PetsMatched implements DomainEvent
{
    public function __construct(
        public readonly Id $matchId,
        public readonly Id $petAId,
        public readonly Id $petBId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
