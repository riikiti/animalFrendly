<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\PublishShelterAnimal;

use App\Modules\Profile\Application\Commands\CreatePet\CreatePetCommand;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetHandler;
use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotVerifiedException;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Публикация карточки животного — это (1) создание анкеты питомца в модуле Profile с
 * purpose=shelter и (2) привязка её к приюту. Переиспользуем готовый use case Profile
 * напрямую, а не дублируем его правила — см. docs/plan/03-architecture.md.
 */
final class PublishShelterAnimalHandler
{
    public function __construct(
        private readonly ShelterRepositoryInterface $shelters,
        private readonly ShelterAnimalRepositoryInterface $shelterAnimals,
        private readonly CreatePetHandler $createPet,
    ) {}

    public function handle(PublishShelterAnimalCommand $command): ShelterAnimal
    {
        $shelter = $this->shelters->findById(Id::fromString($command->shelterId));

        if ($shelter === null) {
            throw ShelterNotFoundException::forId($command->shelterId);
        }

        if (! $shelter->ownerUserId()->equals(Id::fromString($command->actingUserId))) {
            throw NotShelterOwnerException::create();
        }

        if (! $shelter->isVerified()) {
            throw ShelterNotVerifiedException::create();
        }

        $pet = $this->createPet->handle(new CreatePetCommand(
            ownerId: $shelter->ownerUserId()->toString(),
            speciesId: $command->speciesId,
            breedId: $command->breedId,
            name: $command->name,
            sex: $command->sex,
            birthdate: $command->birthdate,
            purpose: 'shelter',
            description: $command->description,
            isVaccinated: $command->isVaccinated,
        ));

        $shelterAnimal = ShelterAnimal::publish($this->shelterAnimals->nextIdentity(), $shelter->id(), $pet->id());
        $this->shelterAnimals->save($shelterAnimal);

        return $shelterAnimal;
    }
}
