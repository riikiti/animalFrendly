<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class AuditLog
{
    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private readonly Id $id,
        private readonly ?Id $actorId,
        private readonly string $action,
        private readonly string $entityType,
        private readonly string $entityId,
        private readonly array $payload,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function create(
        Id $id,
        ?Id $actorId,
        string $action,
        string $entityType,
        string $entityId,
        array $payload = [],
    ): self {
        return new self($id, $actorId, $action, $entityType, $entityId, $payload, new DateTimeImmutable);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function reconstitute(
        Id $id,
        ?Id $actorId,
        string $action,
        string $entityType,
        string $entityId,
        array $payload,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $actorId, $action, $entityType, $entityId, $payload, $createdAt);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function actorId(): ?Id
    {
        return $this->actorId;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function entityType(): string
    {
        return $this->entityType;
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
