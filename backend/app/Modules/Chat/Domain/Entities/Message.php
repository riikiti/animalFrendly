<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Message
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $conversationId,
        private readonly Id $senderId,
        private readonly string $body,
        private readonly DateTimeImmutable $createdAt,
        private readonly ?DateTimeImmutable $readAt,
    ) {}

    public static function send(Id $id, Id $conversationId, Id $senderId, string $body): self
    {
        $body = trim($body);

        if ($body === '') {
            throw new \InvalidArgumentException('Сообщение не может быть пустым.');
        }

        return new self($id, $conversationId, $senderId, $body, new DateTimeImmutable, null);
    }

    public static function reconstitute(
        Id $id,
        Id $conversationId,
        Id $senderId,
        string $body,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $readAt,
    ): self {
        return new self($id, $conversationId, $senderId, $body, $createdAt, $readAt);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function conversationId(): Id
    {
        return $this->conversationId;
    }

    public function senderId(): Id
    {
        return $this->senderId;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function readAt(): ?DateTimeImmutable
    {
        return $this->readAt;
    }
}
