<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Entities;

use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Enums\PetSocialTag;
use App\Modules\Profile\Domain\Enums\PetStatus;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class Pet
{
    /**
     * @param  list<PetSocialTag>  $socialTags
     */
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
        private ?string $photoUrl,
        private readonly array $socialTags,
        private readonly ?Id $parentId = null,
    ) {}

    /**
     * @param  list<PetSocialTag>  $socialTags
     */
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
        array $socialTags = [],
        ?Id $parentId = null,
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
            $socialTags,
            $parentId,
        );
    }

    /**
     * @param  list<PetSocialTag>  $socialTags
     */
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
        ?string $photoUrl = null,
        array $socialTags = [],
        ?Id $parentId = null,
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
            $photoUrl,
            $socialTags,
            $parentId,
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

    public function parentId(): ?Id
    {
        return $this->parentId;
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

    public function setPhoto(string $url): void
    {
        $this->photoUrl = $url;
    }

    public function removePhoto(): void
    {
        $this->photoUrl = null;
    }

    public function photoUrl(): ?string
    {
        return $this->photoUrl;
    }

    /**
     * @return list<PetSocialTag>
     */
    public function socialTags(): array
    {
        return $this->socialTags;
    }
}
