<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\ListMyShelterAnimals;

use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListMyShelterAnimalsHandler
{
    public function __construct(
        private readonly ShelterRepositoryInterface $shelters,
        private readonly ShelterAnimalRepositoryInterface $shelterAnimals,
    ) {}

    /**
     * @return list<ShelterAnimal>
     */
    public function handle(ListMyShelterAnimalsQuery $query): array
    {
        $shelter = $this->shelters->findById(Id::fromString($query->shelterId));

        if ($shelter === null) {
            throw ShelterNotFoundException::forId($query->shelterId);
        }

        if (! $shelter->ownerUserId()->equals(Id::fromString($query->actingUserId))) {
            throw NotShelterOwnerException::create();
        }

        return $this->shelterAnimals->findByShelter($shelter->id());
    }
}
