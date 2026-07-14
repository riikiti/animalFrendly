<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\DecideAdoptionRequest;

use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Events\AdoptionRequestApproved;
use App\Modules\Shelter\Domain\Exceptions\AdoptionRequestNotFoundException;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterAnimalNotFoundException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class DecideAdoptionRequestHandler
{
    public function __construct(
        private readonly AdoptionRequestRepositoryInterface $requests,
        private readonly ShelterAnimalRepositoryInterface $shelterAnimals,
        private readonly ShelterRepositoryInterface $shelters,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(DecideAdoptionRequestCommand $command): AdoptionRequest
    {
        $requestId = Id::fromString($command->adoptionRequestId);
        $request = $this->requests->findById($requestId);

        if ($request === null) {
            throw AdoptionRequestNotFoundException::forId($command->adoptionRequestId);
        }

        $shelterAnimal = $this->shelterAnimals->findById($request->shelterAnimalId());

        if ($shelterAnimal === null) {
            throw ShelterAnimalNotFoundException::forId($request->shelterAnimalId()->toString());
        }

        $shelter = $this->shelters->findById($shelterAnimal->shelterId());

        if ($shelter === null) {
            throw ShelterNotFoundException::forId($shelterAnimal->shelterId()->toString());
        }

        $actingUserId = Id::fromString($command->actingUserId);

        if (! $shelter->ownerUserId()->equals($actingUserId)) {
            throw NotShelterOwnerException::create();
        }

        if ($command->approve) {
            $request->approve($actingUserId);
            $shelterAnimal->reserve();
            $this->shelterAnimals->save($shelterAnimal);
            $this->requests->save($request);

            $this->events->dispatch(new AdoptionRequestApproved($request->id(), new DateTimeImmutable));
        } else {
            $request->reject($actingUserId);
            $this->requests->save($request);
        }

        return $request;
    }
}
