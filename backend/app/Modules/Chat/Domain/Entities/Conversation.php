<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * Беседа привязана ровно к одному из четырёх источников — мэтчу, заявке на усыновление,
 * прямому обращению в приют или прямому обращению к любому пользователю (например,
 * продавцу на маркетплейсе) — см. docs/database/03-matching-chat.md. Оба вида прямого
 * контакта хранят initiatorUserId (в отличие от match/adoption-request, у них нет
 * отдельной сущности-источника участников); shelterAnimalId — опциональная «прикреплённая
 * карточка» животного для контакта с приютом.
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
        private readonly ?Id $recipientUserId = null,
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

    public static function createForDirectContact(
        Id $id,
        Id $recipientUserId,
        Id $initiatorUserId,
    ): self {
        return new self($id, null, null, new DateTimeImmutable, null, $initiatorUserId, null, $recipientUserId);
    }

    public static function reconstitute(
        Id $id,
        ?Id $matchId,
        ?Id $adoptionRequestId,
        DateTimeImmutable $createdAt,
        ?Id $shelterId = null,
        ?Id $initiatorUserId = null,
        ?Id $shelterAnimalId = null,
        ?Id $recipientUserId = null,
    ): self {
        return new self($id, $matchId, $adoptionRequestId, $createdAt, $shelterId, $initiatorUserId, $shelterAnimalId, $recipientUserId);
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

    public function recipientUserId(): ?Id
    {
        return $this->recipientUserId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
