<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Entities;

use App\Modules\Shelter\Domain\Enums\ShelterAnimalStatus;
use App\Modules\Shelter\Domain\Exceptions\ShelterAnimalNotAvailableException;
use App\Shared\Domain\ValueObjects\Id;

final class ShelterAnimal
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $shelterId,
        private readonly Id $petId,
        private ShelterAnimalStatus $status,
    ) {}

    public static function publish(Id $id, Id $shelterId, Id $petId): self
    {
        return new self($id, $shelterId, $petId, ShelterAnimalStatus::Available);
    }

    public static function reconstitute(Id $id, Id $shelterId, Id $petId, ShelterAnimalStatus $status): self
    {
        return new self($id, $shelterId, $petId, $status);
    }

    public function reserve(): void
    {
        if ($this->status !== ShelterAnimalStatus::Available) {
            throw ShelterAnimalNotAvailableException::create();
        }

        $this->status = ShelterAnimalStatus::Reserved;
    }

    public function markAdopted(): void
    {
        $this->status = ShelterAnimalStatus::Adopted;
    }

    public function isAvailable(): bool
    {
        return $this->status === ShelterAnimalStatus::Available;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function shelterId(): Id
    {
        return $this->shelterId;
    }

    public function petId(): Id
    {
        return $this->petId;
    }

    public function status(): ShelterAnimalStatus
    {
        return $this->status;
    }
}
