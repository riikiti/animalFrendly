<?php

declare(strict_types=1);

use App\Modules\Profile\Application\Commands\RemovePetPhoto\RemovePetPhotoCommand;
use App\Modules\Profile\Application\Commands\RemovePetPhoto\RemovePetPhotoHandler;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Entities\PetPhoto;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Exceptions\PetPhotoNotFoundException;
use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

function makeRemoveTestPet(Id $id, Id $ownerId, ?string $photoUrl = null): Pet
{
    $pet = Pet::create(
        id: $id,
        ownerId: $ownerId,
        speciesId: 1,
        breedId: null,
        name: 'Питомец',
        sex: PetSex::Male,
        birthdate: null,
        purpose: PetPurpose::Social,
        description: null,
        isVaccinated: false,
    );

    if ($photoUrl !== null) {
        $pet->setPhoto($photoUrl);
    }

    return $pet;
}

it('removes a non-cover photo without touching the pet cover', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $photoId = Id::generate();
    $pet = makeRemoveTestPet($petId, $ownerId, 'https://cdn.example/cover.jpg');
    $photo = PetPhoto::create($photoId, $petId, Id::generate(), 'https://cdn.example/extra.jpg', false);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldNotReceive('save');

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('findById')->once()->andReturn($photo);
    $photos->shouldReceive('delete')->once()->with(Mockery::on(fn (Id $id) => $id->equals($photoId)));

    $handler = new RemovePetPhotoHandler($pets, $photos);
    $handler->handle(new RemovePetPhotoCommand($petId->toString(), $photoId->toString(), $ownerId->toString()));

    expect($pet->photoUrl())->toBe('https://cdn.example/cover.jpg');
});

it('promotes the next photo to cover when the cover photo is removed', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $photoId = Id::generate();
    $pet = makeRemoveTestPet($petId, $ownerId, 'https://cdn.example/cover.jpg');
    $removedPhoto = PetPhoto::create($photoId, $petId, Id::generate(), 'https://cdn.example/cover.jpg', true);
    $nextPhoto = PetPhoto::create(Id::generate(), $petId, Id::generate(), 'https://cdn.example/next.jpg', false);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldReceive('save')->once()->with(Mockery::on(fn (Pet $p) => $p->photoUrl() === 'https://cdn.example/next.jpg'));

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('findById')->once()->andReturn($removedPhoto);
    $photos->shouldReceive('delete')->once();
    $photos->shouldReceive('findByPetId')->once()->andReturn([$nextPhoto]);
    $photos->shouldReceive('save')->once()->with(Mockery::on(fn (PetPhoto $p) => $p->isPrimary() === true));

    $handler = new RemovePetPhotoHandler($pets, $photos);
    $handler->handle(new RemovePetPhotoCommand($petId->toString(), $photoId->toString(), $ownerId->toString()));
});

it('clears the pet cover when the last photo is removed', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $photoId = Id::generate();
    $pet = makeRemoveTestPet($petId, $ownerId, 'https://cdn.example/cover.jpg');
    $removedPhoto = PetPhoto::create($photoId, $petId, Id::generate(), 'https://cdn.example/cover.jpg', true);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldReceive('save')->once()->with(Mockery::on(fn (Pet $p) => $p->photoUrl() === null));

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('findById')->once()->andReturn($removedPhoto);
    $photos->shouldReceive('delete')->once();
    $photos->shouldReceive('findByPetId')->once()->andReturn([]);

    $handler = new RemovePetPhotoHandler($pets, $photos);
    $handler->handle(new RemovePetPhotoCommand($petId->toString(), $photoId->toString(), $ownerId->toString()));
});

it('rejects removing a photo the actor does not own', function (): void {
    $petId = Id::generate();
    $photoId = Id::generate();
    $pet = makeRemoveTestPet($petId, Id::generate());

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);

    $handler = new RemovePetPhotoHandler($pets, $photos);
    $handler->handle(new RemovePetPhotoCommand($petId->toString(), $photoId->toString(), Id::generate()->toString()));
})->throws(PetNotOwnedByActorException::class);

it('rejects removing a photo that does not belong to the pet', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $photoId = Id::generate();
    $pet = makeRemoveTestPet($petId, $ownerId);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('findById')->once()->andReturn(null);

    $handler = new RemovePetPhotoHandler($pets, $photos);
    $handler->handle(new RemovePetPhotoCommand($petId->toString(), $photoId->toString(), $ownerId->toString()));
})->throws(PetPhotoNotFoundException::class);
