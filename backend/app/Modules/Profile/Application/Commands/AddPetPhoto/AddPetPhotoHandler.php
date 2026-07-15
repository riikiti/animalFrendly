<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\AddPetPhoto;

use App\Modules\Profile\Application\Contracts\MediaUploaderInterface;
use App\Modules\Profile\Domain\Entities\PetPhoto;
use App\Modules\Profile\Domain\Exceptions\PetNotFoundException;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Exceptions\TooManyPetPhotosException;
use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class AddPetPhotoHandler
{
    public const MAX_PHOTOS_PER_PET = 6;

    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly PetPhotoRepositoryInterface $photos,
        private readonly MediaUploaderInterface $mediaUploader,
    ) {}

    public function handle(AddPetPhotoCommand $command): PetPhoto
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

        $existingCount = $this->photos->countForPet($petId);

        if ($existingCount >= self::MAX_PHOTOS_PER_PET) {
            throw TooManyPetPhotosException::create(self::MAX_PHOTOS_PER_PET);
        }

        $uploaded = $this->mediaUploader->upload($command->photo, $actingUserId);
        $isPrimary = $existingCount === 0;

        $photo = PetPhoto::create(
            id: $this->photos->nextIdentity(),
            petId: $petId,
            mediaId: Id::fromString($uploaded->mediaId),
            url: $uploaded->url,
            isPrimary: $isPrimary,
        );
        $this->photos->save($photo);

        if ($isPrimary) {
            $pet->setPhoto($uploaded->url);
            $this->pets->save($pet);
        }

        return $photo;
    }
}
