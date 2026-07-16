<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Entities;

use App\Modules\Marketplace\Domain\Enums\BreederVerificationStatus;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Breeder
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $ownerUserId,
        private BreederVerificationStatus $verificationStatus,
        private ?DateTimeImmutable $verifiedAt,
        private ?Id $verifiedBy,
    ) {}

    public static function register(Id $id, Id $ownerUserId): self
    {
        return new self($id, $ownerUserId, BreederVerificationStatus::Pending, null, null);
    }

    public static function reconstitute(
        Id $id,
        Id $ownerUserId,
        BreederVerificationStatus $verificationStatus,
        ?DateTimeImmutable $verifiedAt,
        ?Id $verifiedBy,
    ): self {
        return new self($id, $ownerUserId, $verificationStatus, $verifiedAt, $verifiedBy);
    }

    public function verify(Id $moderatorId): void
    {
        $this->verificationStatus = BreederVerificationStatus::Verified;
        $this->verifiedAt = new DateTimeImmutable;
        $this->verifiedBy = $moderatorId;
    }

    public function reject(Id $moderatorId): void
    {
        $this->verificationStatus = BreederVerificationStatus::Rejected;
        $this->verifiedAt = new DateTimeImmutable;
        $this->verifiedBy = $moderatorId;
    }

    public function isVerified(): bool
    {
        return $this->verificationStatus === BreederVerificationStatus::Verified;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function ownerUserId(): Id
    {
        return $this->ownerUserId;
    }

    public function verificationStatus(): BreederVerificationStatus
    {
        return $this->verificationStatus;
    }

    public function verifiedAt(): ?DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function verifiedBy(): ?Id
    {
        return $this->verifiedBy;
    }
}
