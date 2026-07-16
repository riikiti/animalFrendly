<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Беседа привязана ровно к одному из трёх источников — мэтчу, заявке на усыновление или
 * прямому обращению в приют (см. docs/database/03-matching-chat.md). Прямое обращение
 * дополнительно хранит initiatorUserId (в отличие от match/adoption-request, у него нет
 * отдельной сущности-источника участников) и опциональный shelterAnimalId — «прикреплённая
 * карточка» животного, о котором идёт речь, если разговор начат с его анкеты.
 */
final class Conversation
{
    private function __construct(
        private readonly Id $id,
        private readonly ?Id $matchId,
        private readonly ?Id $adoptionRequestId,
        private readonly DateTimeImmutable $createdAt,
        private readonly ?Id $shelterId = null,
        private readonly ?Id $initiatorUserId = null,
        private readonly ?Id $shelterAnimalId = null,
    ) {}

    public static function createForMatch(Id $id, Id $matchId): self
    {
        return new self($id, $matchId, null, new DateTimeImmutable);
    }

    public static function createForAdoptionRequest(Id $id, Id $adoptionRequestId): self
    {
        return new self($id, null, $adoptionRequestId, new DateTimeImmutable);
    }

    public static function createForShelterContact(
        Id $id,
        Id $shelterId,
        Id $initiatorUserId,
        ?Id $shelterAnimalId,
    ): self {
        return new self($id, null, null, new DateTimeImmutable, $shelterId, $initiatorUserId, $shelterAnimalId);
    }

    public static function reconstitute(
        Id $id,
        ?Id $matchId,
        ?Id $adoptionRequestId,
        DateTimeImmutable $createdAt,
        ?Id $shelterId = null,
        ?Id $initiatorUserId = null,
        ?Id $shelterAnimalId = null,
    ): self {
        return new self($id, $matchId, $adoptionRequestId, $createdAt, $shelterId, $initiatorUserId, $shelterAnimalId);
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

    public function shelterId(): ?Id
    {
        return $this->shelterId;
    }

    public function initiatorUserId(): ?Id
    {
        return $this->initiatorUserId;
    }

    public function shelterAnimalId(): ?Id
    {
        return $this->shelterAnimalId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
