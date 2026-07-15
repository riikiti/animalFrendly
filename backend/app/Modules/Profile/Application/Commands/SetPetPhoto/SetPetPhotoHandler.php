<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\SetPetPhoto;

use App\Modules\Profile\Application\Contracts\MediaUploaderInterface;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Exceptions\PetNotFoundException;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class SetPetPhotoHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly MediaUploaderInterface $mediaUploader,
    ) {}

    public function handle(SetPetPhotoCommand $command): Pet
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

        $uploaded = $this->mediaUploader->upload($command->photo, $actingUserId);
        $pet->setPhoto(Id::fromString($uploaded->mediaId), $uploaded->url);
        $this->pets->save($pet);

        return $pet;
    }
}
