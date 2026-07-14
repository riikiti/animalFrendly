<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * На данный момент беседа привязана только к мэтчу. Когда появится модуль Shelter,
 * добавится второй источник — заявка на усыновление (см. docs/database/03-matching-chat.md,
 * ровно один из двух должен быть заполнен).
 */
final class Conversation
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $matchId,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function createForMatch(Id $id, Id $matchId): self
    {
        return new self($id, $matchId, new DateTimeImmutable);
    }

    public static function reconstitute(Id $id, Id $matchId, DateTimeImmutable $createdAt): self
    {
        return new self($id, $matchId, $createdAt);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function matchId(): Id
    {
        return $this->matchId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
