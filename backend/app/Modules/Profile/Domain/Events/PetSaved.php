<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Events;

use App\Shared\Domain\DomainEvent;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Публикуется при любом сохранении питомца (создание, смена фото, буст) — намеренно грубая
 * гранулярность, "что-то в питомце изменилось, пересчитай". Модуль Search подписывается и
 * переиндексирует документ, см. docs/plan/01-modules.md #13.
 */
final class PetSaved implements DomainEvent
{
    public function __construct(
        public readonly Id $petId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
