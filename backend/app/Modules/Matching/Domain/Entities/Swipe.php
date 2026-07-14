<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Entities;

use App\Modules\Matching\Domain\Enums\SwipeAction;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Факт свайпа — журнальная запись, не агрегат с поведением. Первичный ключ таблицы —
 * bigint (высокообъёмная таблица), см. docs/database/03-matching-chat.md, поэтому здесь
 * нет Id-идентификатора самой записи — только то, что нужно бизнес-правилам.
 */
final class Swipe
{
    public function __construct(
        public readonly Id $swiperPetId,
        public readonly Id $targetPetId,
        public readonly SwipeAction $action,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
