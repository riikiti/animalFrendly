<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Services;

use App\Modules\Chat\Domain\Exceptions\AdoptionRequestNotFoundException;
use App\Modules\Chat\Domain\Exceptions\ConversationAccessDeniedException;
use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Явный межмодульный контракт: Chat опирается на Domain-репозитории Shelter, а не на его
 * Eloquent-модели — тот же принцип, что и MatchParticipantGuard.
 */
final class AdoptionRequestParticipantGuard
{
    public function __construct(
        private readonly AdoptionRequestRepositoryInterface $requests,
        private readonly ShelterAnimalRepositoryInterface $shelterAnimals,
        private readonly ShelterRepositoryInterface $shelters,
    ) {}

    public function assertParticipant(Id $adoptionRequestId, Id $actingUserId): AdoptionRequest
    {
        $request = $this->requests->findById($adoptionRequestId);

        if ($request === null) {
            throw AdoptionRequestNotFoundException::forId($adoptionRequestId->toString());
        }

        if ($request->requesterUserId()->equals($actingUserId)) {
            return $request;
        }

        $shelterAnimal = $this->shelterAnimals->findById($request->shelterAnimalId());
        $shelter = $shelterAnimal !== null ? $this->shelters->findById($shelterAnimal->shelterId()) : null;

        if ($shelter === null || ! $shelter->ownerUserId()->equals($actingUserId)) {
            throw ConversationAccessDeniedException::create();
        }

        return $request;
    }

    public function otherParticipantId(AdoptionRequest $request, Id $senderId): ?Id
    {
        if ($request->requesterUserId()->equals($senderId)) {
            $shelterAnimal = $this->shelterAnimals->findById($request->shelterAnimalId());
            $shelter = $shelterAnimal !== null ? $this->shelters->findById($shelterAnimal->shelterId()) : null;

            return $shelter?->ownerUserId();
        }

        return $request->requesterUserId();
    }
}
