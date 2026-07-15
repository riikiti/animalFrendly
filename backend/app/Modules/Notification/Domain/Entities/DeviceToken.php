<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Entities;

use App\Modules\Notification\Domain\Enums\DevicePlatform;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class DeviceToken
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $userId,
        private readonly DevicePlatform $platform,
        private readonly string $fcmToken,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $lastUsedAt,
    ) {}

    public static function create(Id $id, Id $userId, DevicePlatform $platform, string $fcmToken): self
    {
        $now = new DateTimeImmutable;

        return new self($id, $userId, $platform, $fcmToken, $now, $now);
    }

    public static function reconstitute(
        Id $id,
        Id $userId,
        DevicePlatform $platform,
        string $fcmToken,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $lastUsedAt,
    ): self {
        return new self($id, $userId, $platform, $fcmToken, $createdAt, $lastUsedAt);
    }

    public function touch(): void
    {
        $this->lastUsedAt = new DateTimeImmutable;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function userId(): Id
    {
        return $this->userId;
    }

    public function platform(): DevicePlatform
    {
        return $this->platform;
    }

    public function fcmToken(): string
    {
        return $this->fcmToken;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lastUsedAt(): DateTimeImmutable
    {
        return $this->lastUsedAt;
    }
}
