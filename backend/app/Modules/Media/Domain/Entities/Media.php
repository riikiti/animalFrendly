<?php

declare(strict_types=1);

namespace App\Modules\Media\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Media
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $ownerUserId,
        private readonly string $disk,
        private readonly string $path,
        private readonly string $mimeType,
        private readonly int $sizeBytes,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        Id $id,
        Id $ownerUserId,
        string $disk,
        string $path,
        string $mimeType,
        int $sizeBytes,
    ): self {
        return new self($id, $ownerUserId, $disk, $path, $mimeType, $sizeBytes, new DateTimeImmutable);
    }

    public static function reconstitute(
        Id $id,
        Id $ownerUserId,
        string $disk,
        string $path,
        string $mimeType,
        int $sizeBytes,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $ownerUserId, $disk, $path, $mimeType, $sizeBytes, $createdAt);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function ownerUserId(): Id
    {
        return $this->ownerUserId;
    }

    public function disk(): string
    {
        return $this->disk;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function sizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
