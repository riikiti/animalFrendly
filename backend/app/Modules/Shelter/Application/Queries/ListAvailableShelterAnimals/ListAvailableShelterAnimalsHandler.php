<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\ListAvailableShelterAnimals;

use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;

final class ListAvailableShelterAnimalsHandler
{
    public function __construct(private readonly ShelterAnimalRepositoryInterface $shelterAnimals) {}

    /**
     * @return list<ShelterAnimal>
     */
    public function handle(int $limit = 20): array
    {
        return $this->shelterAnimals->findAvailable($limit);
    }
}
