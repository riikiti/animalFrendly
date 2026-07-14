<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Entities;

use App\Modules\Shelter\Domain\Enums\AdoptionRequestStatus;
use App\Modules\Shelter\Domain\Exceptions\AdoptionRequestAlreadyDecidedException;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class AdoptionRequest
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $shelterAnimalId,
        private readonly Id $requesterUserId,
        private readonly ?string $message,
        private AdoptionRequestStatus $status,
        private ?DateTimeImmutable $decidedAt,
        private ?Id $decidedBy,
    ) {}

    public static function create(Id $id, Id $shelterAnimalId, Id $requesterUserId, ?string $message): self
    {
        return new self($id, $shelterAnimalId, $requesterUserId, $message, AdoptionRequestStatus::Pending, null, null);
    }

    public static function reconstitute(
        Id $id,
        Id $shelterAnimalId,
        Id $requesterUserId,
        ?string $message,
        AdoptionRequestStatus $status,
        ?DateTimeImmutable $decidedAt,
        ?Id $decidedBy,
    ): self {
        return new self($id, $shelterAnimalId, $requesterUserId, $message, $status, $decidedAt, $decidedBy);
    }

    public function approve(Id $decidedBy): void
    {
        $this->assertPending();
        $this->status = AdoptionRequestStatus::Approved;
        $this->decidedAt = new DateTimeImmutable;
        $this->decidedBy = $decidedBy;
    }

    public function reject(Id $decidedBy): void
    {
        $this->assertPending();
        $this->status = AdoptionRequestStatus::Rejected;
        $this->decidedAt = new DateTimeImmutable;
        $this->decidedBy = $decidedBy;
    }

    public function cancel(): void
    {
        $this->assertPending();
        $this->status = AdoptionRequestStatus::Cancelled;
        $this->decidedAt = new DateTimeImmutable;
    }

    private function assertPending(): void
    {
        if ($this->status !== AdoptionRequestStatus::Pending) {
            throw AdoptionRequestAlreadyDecidedException::create();
        }
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function shelterAnimalId(): Id
    {
        return $this->shelterAnimalId;
    }

    public function requesterUserId(): Id
    {
        return $this->requesterUserId;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function status(): AdoptionRequestStatus
    {
        return $this->status;
    }

    public function decidedAt(): ?DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function decidedBy(): ?Id
    {
        return $this->decidedBy;
    }
}
