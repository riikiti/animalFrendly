<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\RemovePetPhoto;

use App\Modules\Profile\Domain\Events\PetSaved;
use App\Modules\Profile\Domain\Exceptions\PetNotFoundException;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Exceptions\PetPhotoNotFoundException;
use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class RemovePetPhotoHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly PetPhotoRepositoryInterface $photos,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(RemovePetPhotoCommand $command): void
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

        $wasPrimary = $photo->isPrimary();
        $this->photos->delete($photoId);

        if (! $wasPrimary) {
            return;
        }

        $remaining = $this->photos->findByPetId($petId);
        $nextPrimary = $remaining[0] ?? null;

        if ($nextPrimary === null) {
            $pet->removePhoto();
            $this->pets->save($pet);
            $this->events->dispatch(new PetSaved($petId, new DateTimeImmutable));

            return;
        }

        $nextPrimary->markPrimary();
        $this->photos->save($nextPrimary);
        $pet->setPhoto($nextPrimary->url());
        $this->pets->save($pet);
        $this->events->dispatch(new PetSaved($petId, new DateTimeImmutable));
    }
}
