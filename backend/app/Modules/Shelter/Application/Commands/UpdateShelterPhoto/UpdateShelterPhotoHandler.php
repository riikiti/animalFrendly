<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\UpdateShelterPhoto;

use App\Modules\Shelter\Application\Contracts\MediaUploaderInterface;
use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class UpdateShelterPhotoHandler
{
    public function __construct(
        private readonly ShelterRepositoryInterface $shelters,
        private readonly MediaUploaderInterface $mediaUploader,
    ) {}

    public function handle(UpdateShelterPhotoCommand $command): Shelter
    {
        $shelterId = Id::fromString($command->shelterId);
        $shelter = $this->shelters->findById($shelterId);

        if ($shelter === null) {
            throw ShelterNotFoundException::forId($command->shelterId);
        }

        $actingUserId = Id::fromString($command->actingUserId);

        if (! $shelter->ownerUserId()->equals($actingUserId)) {
            throw NotShelterOwnerException::create();
        }

        $uploaded = $this->mediaUploader->upload($command->photo, $actingUserId);
        $shelter->setPhoto($uploaded->url);
        $this->shelters->save($shelter);

        return $shelter;
    }
}
