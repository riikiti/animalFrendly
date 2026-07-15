<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Entities;

use App\Modules\Notification\Domain\Enums\NotificationChannel;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Notification
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(
        private readonly Id $id,
        private readonly Id $userId,
        private readonly NotificationType $type,
        private readonly string $message,
        private readonly array $data,
        private readonly NotificationChannel $channel,
        private ?DateTimeImmutable $readAt,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(
        Id $id,
        Id $userId,
        NotificationType $type,
        string $message,
        array $data = [],
    ): self {
        return new self($id, $userId, $type, $message, $data, NotificationChannel::InApp, null, new DateTimeImmutable);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function reconstitute(
        Id $id,
        Id $userId,
        NotificationType $type,
        string $message,
        array $data,
        NotificationChannel $channel,
        ?DateTimeImmutable $readAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $userId, $type, $message, $data, $channel, $readAt, $createdAt);
    }

    public function markRead(): void
    {
        $this->readAt ??= new DateTimeImmutable;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function userId(): Id
    {
        return $this->userId;
    }

    public function type(): NotificationType
    {
        return $this->type;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function channel(): NotificationChannel
    {
        return $this->channel;
    }

    public function readAt(): ?DateTimeImmutable
    {
        return $this->readAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
