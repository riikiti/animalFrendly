<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Repositories;

use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Shared\Domain\ValueObjects\Id;

interface AdoptionRequestRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(AdoptionRequest $request): void;

    public function findById(Id $id): ?AdoptionRequest;

    /**
     * @return list<AdoptionRequest>
     */
    public function findByShelterAnimal(Id $shelterAnimalId): array;

    /**
     * @return list<AdoptionRequest>
     */
    public function findByRequester(Id $requesterUserId): array;

    /**
     * @param  list<Id>  $shelterAnimalIds
     * @return list<AdoptionRequest>
     */
    public function findPendingForShelterAnimals(array $shelterAnimalIds): array;
}
