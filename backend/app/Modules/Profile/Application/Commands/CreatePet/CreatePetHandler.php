<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\CreatePet;

use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Profile\Application\Contracts\SubscriptionFeatureGateInterface;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Enums\PetSocialTag;
use App\Modules\Profile\Domain\Events\PetSaved;
use App\Modules\Profile\Domain\Exceptions\BreedDoesNotBelongToSpeciesException;
use App\Modules\Profile\Domain\Exceptions\PetLimitExceededException;
use App\Modules\Profile\Domain\Exceptions\SpeciesNotFoundException;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class CreatePetHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly SpeciesRepositoryInterface $species,
        private readonly BreedRepositoryInterface $breeds,
        private readonly SubscriptionFeatureGateInterface $featureGate,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(CreatePetCommand $command): Pet
    {
        if ($this->species->findById($command->speciesId) === null) {
            throw SpeciesNotFoundException::forId($command->speciesId);
        }

        if ($command->breedId !== null) {
            $breed = $this->breeds->findById($command->breedId);

            if ($breed === null || $breed->speciesId !== $command->speciesId) {
                throw BreedDoesNotBelongToSpeciesException::create($command->breedId, $command->speciesId);
            }
        }

        $ownerId = Id::fromString($command->ownerId);

        // Бесплатный тариф — одна анкета на пользователя; подписка снимает ограничение, см.
        // Subscription\Infrastructure\Adapters\ProfileFeatureGateAdapter.
        if ($this->pets->countByOwner($ownerId) >= 1 && ! $this->featureGate->hasUnlimitedPets($ownerId)) {
            throw PetLimitExceededException::create();
        }

        $pet = Pet::create(
            id: $this->pets->nextIdentity(),
            ownerId: $ownerId,
            speciesId: $command->speciesId,
            breedId: $command->breedId,
            name: $command->name,
            sex: PetSex::from($command->sex),
            birthdate: $command->birthdate !== null ? new DateTimeImmutable($command->birthdate) : null,
            purpose: PetPurpose::from($command->purpose),
            description: $command->description,
            isVaccinated: $command->isVaccinated,
            socialTags: array_map(PetSocialTag::from(...), $command->socialTags),
        );

        $this->pets->save($pet);
        $this->events->dispatch(new PetSaved($pet->id(), new DateTimeImmutable));

        return $pet;
    }
}
