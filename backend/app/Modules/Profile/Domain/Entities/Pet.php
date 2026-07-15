<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Entities;

use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Enums\PetStatus;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Pet
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $ownerId,
        private readonly int $speciesId,
        private readonly ?int $breedId,
        private string $name,
        private readonly PetSex $sex,
        private readonly ?DateTimeImmutable $birthdate,
        private readonly PetPurpose $purpose,
        private readonly ?string $description,
        private readonly bool $isVaccinated,
        private PetStatus $status,
        private ?DateTimeImmutable $boostedUntil,
        private ?Id $photoMediaId,
        private ?string $photoUrl,
    ) {}

    public static function create(
        Id $id,
        Id $ownerId,
        int $speciesId,
        ?int $breedId,
        string $name,
        PetSex $sex,
        ?DateTimeImmutable $birthdate,
        PetPurpose $purpose,
        ?string $description,
        bool $isVaccinated,
    ): self {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Кличка питомца не может быть пустой.');
        }

        return new self(
            $id,
            $ownerId,
            $speciesId,
            $breedId,
            $name,
            $sex,
            $birthdate,
            $purpose,
            $description,
            $isVaccinated,
            PetStatus::Active,
            null,
            null,
            null,
        );
    }

    public static function reconstitute(
        Id $id,
        Id $ownerId,
        int $speciesId,
        ?int $breedId,
        string $name,
        PetSex $sex,
        ?DateTimeImmutable $birthdate,
        PetPurpose $purpose,
        ?string $description,
        bool $isVaccinated,
        PetStatus $status,
        ?DateTimeImmutable $boostedUntil = null,
        ?Id $photoMediaId = null,
        ?string $photoUrl = null,
    ): self {
        return new self(
            $id,
            $ownerId,
            $speciesId,
            $breedId,
            $name,
            $sex,
            $birthdate,
            $purpose,
            $description,
            $isVaccinated,
            $status,
            $boostedUntil,
            $photoMediaId,
            $photoUrl,
        );
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function ownerId(): Id
    {
        return $this->ownerId;
    }

    public function speciesId(): int
    {
        return $this->speciesId;
    }

    public function breedId(): ?int
    {
        return $this->breedId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sex(): PetSex
    {
        return $this->sex;
    }

    public function birthdate(): ?DateTimeImmutable
    {
        return $this->birthdate;
    }

    public function purpose(): PetPurpose
    {
        return $this->purpose;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isVaccinated(): bool
    {
        return $this->isVaccinated;
    }

    public function status(): PetStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === PetStatus::Active;
    }

    public function boost(DateTimeImmutable $until): void
    {
        $this->boostedUntil = $until;
    }

    public function boostedUntil(): ?DateTimeImmutable
    {
        return $this->boostedUntil;
    }

    public function isBoosted(): bool
    {
        return $this->boostedUntil !== null && $this->boostedUntil > new DateTimeImmutable;
    }

    public function setPhoto(Id $mediaId, string $url): void
    {
        $this->photoMediaId = $mediaId;
        $this->photoUrl = $url;
    }

    public function removePhoto(): void
    {
        $this->photoMediaId = null;
        $this->photoUrl = null;
    }

    public function photoMediaId(): ?Id
    {
        return $this->photoMediaId;
    }

    public function photoUrl(): ?string
    {
        return $this->photoUrl;
    }
}
