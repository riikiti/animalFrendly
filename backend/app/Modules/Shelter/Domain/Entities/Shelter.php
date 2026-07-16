<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Entities;

use App\Modules\Shelter\Domain\Enums\ShelterVerificationStatus;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Shelter
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $ownerUserId,
        private readonly string $legalName,
        private readonly ?string $inn,
        private readonly ?string $description,
        private ShelterVerificationStatus $verificationStatus,
        private ?DateTimeImmutable $verifiedAt,
        private ?Id $verifiedBy,
        private ?string $address = null,
        private ?string $city = null,
        private ?float $latitude = null,
        private ?float $longitude = null,
        private ?string $phone = null,
        private ?string $email = null,
        private ?string $photoUrl = null,
    ) {}

    public static function register(
        Id $id,
        Id $ownerUserId,
        string $legalName,
        ?string $inn,
        ?string $description,
    ): self {
        $legalName = trim($legalName);

        if ($legalName === '') {
            throw new \InvalidArgumentException('Название приюта не может быть пустым.');
        }

        return new self($id, $ownerUserId, $legalName, $inn, $description, ShelterVerificationStatus::Pending, null, null);
    }

    public static function reconstitute(
        Id $id,
        Id $ownerUserId,
        string $legalName,
        ?string $inn,
        ?string $description,
        ShelterVerificationStatus $verificationStatus,
        ?DateTimeImmutable $verifiedAt,
        ?Id $verifiedBy,
        ?string $address = null,
        ?string $city = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $photoUrl = null,
    ): self {
        return new self(
            $id,
            $ownerUserId,
            $legalName,
            $inn,
            $description,
            $verificationStatus,
            $verifiedAt,
            $verifiedBy,
            $address,
            $city,
            $latitude,
            $longitude,
            $phone,
            $email,
            $photoUrl,
        );
    }

    public function verify(Id $moderatorId): void
    {
        $this->verificationStatus = ShelterVerificationStatus::Verified;
        $this->verifiedAt = new DateTimeImmutable;
        $this->verifiedBy = $moderatorId;
    }

    public function reject(Id $moderatorId): void
    {
        $this->verificationStatus = ShelterVerificationStatus::Rejected;
        $this->verifiedAt = new DateTimeImmutable;
        $this->verifiedBy = $moderatorId;
    }

    public function isVerified(): bool
    {
        return $this->verificationStatus === ShelterVerificationStatus::Verified;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function ownerUserId(): Id
    {
        return $this->ownerUserId;
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function inn(): ?string
    {
        return $this->inn;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function verificationStatus(): ShelterVerificationStatus
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

    public function address(): ?string
    {
        return $this->address;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function latitude(): ?float
    {
        return $this->latitude;
    }

    public function longitude(): ?float
    {
        return $this->longitude;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function photoUrl(): ?string
    {
        return $this->photoUrl;
    }

    /**
     * Город и координаты приходят из геокодера вместе с адресом и всегда согласованы —
     * тот же приём, что Identity\Domain\Entities\User::setLocation().
     */
    public function setLocation(?string $address, ?string $city, ?float $latitude, ?float $longitude): void
    {
        $this->address = $address;
        $this->city = $city;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function setContactInfo(?string $phone, ?string $email): void
    {
        $this->phone = $phone;
        $this->email = $email;
    }

    public function setPhoto(?string $url): void
    {
        $this->photoUrl = $url;
    }
}
