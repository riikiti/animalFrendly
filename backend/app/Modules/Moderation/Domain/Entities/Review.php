<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;
use InvalidArgumentException;

final class Review
{
    private function __construct(
        private readonly Id $id,
        private readonly ?Id $orderId,
        private readonly ?Id $adoptionRequestId,
        private readonly Id $authorId,
        private readonly Id $targetUserId,
        private readonly int $rating,
        private readonly ?string $comment,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        Id $id,
        ?Id $orderId,
        ?Id $adoptionRequestId,
        Id $authorId,
        Id $targetUserId,
        int $rating,
        ?string $comment,
    ): self {
        if (($orderId === null) === ($adoptionRequestId === null)) {
            throw new InvalidArgumentException('Ровно одно из orderId/adoptionRequestId должно быть задано.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Оценка должна быть от 1 до 5.');
        }

        return new self($id, $orderId, $adoptionRequestId, $authorId, $targetUserId, $rating, $comment, new DateTimeImmutable);
    }

    public static function reconstitute(
        Id $id,
        ?Id $orderId,
        ?Id $adoptionRequestId,
        Id $authorId,
        Id $targetUserId,
        int $rating,
        ?string $comment,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $orderId, $adoptionRequestId, $authorId, $targetUserId, $rating, $comment, $createdAt);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function orderId(): ?Id
    {
        return $this->orderId;
    }

    public function adoptionRequestId(): ?Id
    {
        return $this->adoptionRequestId;
    }

    public function authorId(): Id
    {
        return $this->authorId;
    }

    public function targetUserId(): Id
    {
        return $this->targetUserId;
    }

    public function rating(): int
    {
        return $this->rating;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
