<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Commands\BoostPet;

use App\Modules\Matching\Application\Contracts\SubscriptionFeatureGateInterface;
use App\Modules\Matching\Domain\Exceptions\BoostQuotaExceededException;
use App\Modules\Matching\Domain\Exceptions\PetNotFoundException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Events\PetSaved;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateInterval;
use DateTimeImmutable;

final class BoostPetHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly SubscriptionFeatureGateInterface $featureGate,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(BoostPetCommand $command): Pet
    {
        $petId = Id::fromString($command->petId);
        $actingUserId = Id::fromString($command->actingUserId);

        $pet = $this->pets->findById($petId);

        if ($pet === null) {
            throw PetNotFoundException::forId($command->petId);
        }

        if (! $pet->ownerId()->equals($actingUserId)) {
            throw PetNotOwnedByActorException::create();
        }

        if (! $this->featureGate->consume($actingUserId, 'boost')) {
            throw BoostQuotaExceededException::create();
        }

        $pet->boost((new DateTimeImmutable)->add(new DateInterval('P30D')));
        $this->pets->save($pet);
        $this->events->dispatch(new PetSaved($petId, new DateTimeImmutable));

        return $pet;
    }
}
