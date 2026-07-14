<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Repositories;

use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Shared\Domain\ValueObjects\Id;

interface ShelterAnimalRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(ShelterAnimal $shelterAnimal): void;

    public function findById(Id $id): ?ShelterAnimal;

    /**
     * @return list<ShelterAnimal>
     */
    public function findAvailable(int $limit = 20): array;

    /**
     * @return list<ShelterAnimal>
     */
    public function findByShelter(Id $shelterId): array;
}
