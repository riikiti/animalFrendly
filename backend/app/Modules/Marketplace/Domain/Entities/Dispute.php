<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Entities;

use App\Modules\Marketplace\Domain\Enums\DisputeResolution;
use App\Modules\Marketplace\Domain\Exceptions\DisputeAlreadyResolvedException;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Dispute
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $orderId,
        private readonly Id $openedBy,
        private readonly string $reason,
        private ?DisputeResolution $resolution,
        private ?Id $resolvedBy,
        private ?DateTimeImmutable $resolvedAt,
    ) {}

    public static function open(Id $id, Id $orderId, Id $openedBy, string $reason): self
    {
        return new self($id, $orderId, $openedBy, $reason, null, null, null);
    }

    public static function reconstitute(
        Id $id,
        Id $orderId,
        Id $openedBy,
        string $reason,
        ?DisputeResolution $resolution,
        ?Id $resolvedBy,
        ?DateTimeImmutable $resolvedAt,
    ): self {
        return new self($id, $orderId, $openedBy, $reason, $resolution, $resolvedBy, $resolvedAt);
    }

    public function resolve(DisputeResolution $resolution, Id $resolvedBy): void
    {
        if ($this->resolution !== null) {
            throw DisputeAlreadyResolvedException::create();
        }

        $this->resolution = $resolution;
        $this->resolvedBy = $resolvedBy;
        $this->resolvedAt = new DateTimeImmutable;
    }

    public function isResolved(): bool
    {
        return $this->resolution !== null;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function orderId(): Id
    {
        return $this->orderId;
    }

    public function openedBy(): Id
    {
        return $this->openedBy;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function resolution(): ?DisputeResolution
    {
        return $this->resolution;
    }

    public function resolvedBy(): ?Id
    {
        return $this->resolvedBy;
    }

    public function resolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }
}
