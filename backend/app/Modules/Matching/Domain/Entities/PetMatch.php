<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class PetMatch
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $petAId,
        private readonly Id $petBId,
        private readonly DateTimeImmutable $matchedAt,
        private readonly ?DateTimeImmutable $unmatchedAt,
    ) {}

    /**
     * pet_a_id < pet_b_id по конвенции — см. docs/database/03-matching-chat.md.
     */
    public static function create(Id $id, Id $petOneId, Id $petTwoId): self
    {
        [$petAId, $petBId] = self::ordered($petOneId, $petTwoId);

        return new self($id, $petAId, $petBId, new DateTimeImmutable, null);
    }

    public static function reconstitute(
        Id $id,
        Id $petAId,
        Id $petBId,
        DateTimeImmutable $matchedAt,
        ?DateTimeImmutable $unmatchedAt,
    ): self {
        return new self($id, $petAId, $petBId, $matchedAt, $unmatchedAt);
    }

    /**
     * @return array{0: Id, 1: Id}
     */
    public static function ordered(Id $petOneId, Id $petTwoId): array
    {
        return $petOneId->toString() < $petTwoId->toString()
            ? [$petOneId, $petTwoId]
            : [$petTwoId, $petOneId];
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function petAId(): Id
    {
        return $this->petAId;
    }

    public function petBId(): Id
    {
        return $this->petBId;
    }

    public function matchedAt(): DateTimeImmutable
    {
        return $this->matchedAt;
    }

    public function unmatchedAt(): ?DateTimeImmutable
    {
        return $this->unmatchedAt;
    }
}
