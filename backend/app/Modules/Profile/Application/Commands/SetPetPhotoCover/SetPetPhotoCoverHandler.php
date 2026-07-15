<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\SetPetPhotoCover;

use App\Modules\Profile\Domain\Entities\PetPhoto;
use App\Modules\Profile\Domain\Events\PetSaved;
use App\Modules\Profile\Domain\Exceptions\PetNotFoundException;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Exceptions\PetPhotoNotFoundException;
use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class SetPetPhotoCoverHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly PetPhotoRepositoryInterface $photos,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(SetPetPhotoCoverCommand $command): PetPhoto
    {
        $petId = Id::fromString($command->petId);
        $photoId = Id::fromString($command->photoId);
        $actingUserId = Id::fromString($command->actingUserId);

        $pet = $this->pets->findById($petId);

        if ($pet === null) {
            throw PetNotFoundException::forId($command->petId);
        }

        if (! $pet->ownerId()->equals($actingUserId)) {
            throw PetNotOwnedByActorException::create();
        }

        $photo = $this->photos->findById($photoId);

        if ($photo === null || ! $photo->petId()->equals($petId)) {
            throw PetPhotoNotFoundException::forId($command->photoId);
        }

        $this->photos->clearPrimaryForPet($petId, $photoId);
        $photo->markPrimary();
        $this->photos->save($photo);

        $pet->setPhoto($photo->url());
        $this->pets->save($pet);
        $this->events->dispatch(new PetSaved($petId, new DateTimeImmutable));

        return $photo;
    }
}
