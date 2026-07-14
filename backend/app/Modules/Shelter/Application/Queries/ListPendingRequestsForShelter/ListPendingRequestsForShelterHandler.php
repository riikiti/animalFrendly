<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\ListPendingRequestsForShelter;

use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListPendingRequestsForShelterHandler
{
    public function __construct(
        private readonly ShelterRepositoryInterface $shelters,
        private readonly ShelterAnimalRepositoryInterface $shelterAnimals,
        private readonly AdoptionRequestRepositoryInterface $requests,
    ) {}

    /**
     * @return list<AdoptionRequest>
     */
    public function handle(ListPendingRequestsForShelterQuery $query): array
    {
        $shelterId = Id::fromString($query->shelterId);
        $shelter = $this->shelters->findById($shelterId);

        if ($shelter === null) {
            throw ShelterNotFoundException::forId($query->shelterId);
        }

        if (! $shelter->ownerUserId()->equals(Id::fromString($query->actingUserId))) {
            throw NotShelterOwnerException::create();
        }

        $animalIds = array_map(
            static fn (ShelterAnimal $animal): Id => $animal->id(),
            $this->shelterAnimals->findByShelter($shelterId),
        );

        return $this->requests->findPendingForShelterAnimals($animalIds);
    }
}
