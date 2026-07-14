<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Беседа привязана либо к мэтчу, либо к заявке на усыновление — ровно один из двух,
 * см. docs/database/03-matching-chat.md.
 */
final class Conversation
{
    private function __construct(
        private readonly Id $id,
        private readonly ?Id $matchId,
        private readonly ?Id $adoptionRequestId,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function createForMatch(Id $id, Id $matchId): self
    {
        return new self($id, $matchId, null, new DateTimeImmutable);
    }

    public static function createForAdoptionRequest(Id $id, Id $adoptionRequestId): self
    {
        return new self($id, null, $adoptionRequestId, new DateTimeImmutable);
    }

    public static function reconstitute(
        Id $id,
        ?Id $matchId,
        ?Id $adoptionRequestId,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $matchId, $adoptionRequestId, $createdAt);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function matchId(): ?Id
    {
        return $this->matchId;
    }

    public function adoptionRequestId(): ?Id
    {
        return $this->adoptionRequestId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
