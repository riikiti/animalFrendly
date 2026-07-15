<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class PetPhoto
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $petId,
        private readonly Id $mediaId,
        private readonly string $url,
        private bool $isPrimary,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(Id $id, Id $petId, Id $mediaId, string $url, bool $isPrimary): self
    {
        return new self($id, $petId, $mediaId, $url, $isPrimary, new DateTimeImmutable);
    }

    public static function reconstitute(
        Id $id,
        Id $petId,
        Id $mediaId,
        string $url,
        bool $isPrimary,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $petId, $mediaId, $url, $isPrimary, $createdAt);
    }

    public function markPrimary(): void
    {
        $this->isPrimary = true;
    }

    public function unmarkPrimary(): void
    {
        $this->isPrimary = false;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function petId(): Id
    {
        return $this->petId;
    }

    public function mediaId(): Id
    {
        return $this->mediaId;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
