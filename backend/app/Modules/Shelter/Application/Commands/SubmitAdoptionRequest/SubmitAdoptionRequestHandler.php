<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\SubmitAdoptionRequest;

use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Exceptions\ShelterAnimalNotAvailableException;
use App\Modules\Shelter\Domain\Exceptions\ShelterAnimalNotFoundException;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class SubmitAdoptionRequestHandler
{
    public function __construct(
        private readonly ShelterAnimalRepositoryInterface $shelterAnimals,
        private readonly AdoptionRequestRepositoryInterface $requests,
    ) {}

    public function handle(SubmitAdoptionRequestCommand $command): AdoptionRequest
    {
        $shelterAnimalId = Id::fromString($command->shelterAnimalId);
        $shelterAnimal = $this->shelterAnimals->findById($shelterAnimalId);

        if ($shelterAnimal === null) {
            throw ShelterAnimalNotFoundException::forId($command->shelterAnimalId);
        }

        if (! $shelterAnimal->isAvailable()) {
            throw ShelterAnimalNotAvailableException::create();
        }

        $request = AdoptionRequest::create(
            id: $this->requests->nextIdentity(),
            shelterAnimalId: $shelterAnimalId,
            requesterUserId: Id::fromString($command->requesterUserId),
            message: $command->message,
        );

        $this->requests->save($request);

        return $request;
    }
}
