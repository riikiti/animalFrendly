<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\RemovePetPhoto;

use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Exceptions\PetNotFoundException;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class RemovePetPhotoHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
    ) {}

    public function handle(RemovePetPhotoCommand $command): Pet
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

        $pet->removePhoto();
        $this->pets->save($pet);

        return $pet;
    }
}
