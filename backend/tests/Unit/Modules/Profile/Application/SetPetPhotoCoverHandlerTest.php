<?php

declare(strict_types=1);

use App\Modules\Profile\Application\Commands\SetPetPhotoCover\SetPetPhotoCoverCommand;
use App\Modules\Profile\Application\Commands\SetPetPhotoCover\SetPetPhotoCoverHandler;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Entities\PetPhoto;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Exceptions\PetPhotoNotFoundException;
use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

it('makes a non-cover photo the new cover', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $photoId = Id::generate();

    $pet = Pet::create($petId, $ownerId, 1, null, 'Питомец', PetSex::Male, null, PetPurpose::Social, null, false);
    $pet->setPhoto('https://cdn.example/old-cover.jpg');

    $photo = PetPhoto::create($photoId, $petId, Id::generate(), 'https://cdn.example/new-cover.jpg', false);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldReceive('save')->once()->with(Mockery::on(fn (Pet $p) => $p->photoUrl() === 'https://cdn.example/new-cover.jpg'));

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('findById')->once()->andReturn($photo);
    $photos->shouldReceive('clearPrimaryForPet')->once()->with(
        Mockery::on(fn (Id $id) => $id->equals($petId)),
        Mockery::on(fn (Id $id) => $id->equals($photoId)),
    );
    $photos->shouldReceive('save')->once()->with(Mockery::on(fn (PetPhoto $p) => $p->isPrimary() === true));

    $handler = new SetPetPhotoCoverHandler($pets, $photos);
    $result = $handler->handle(new SetPetPhotoCoverCommand($petId->toString(), $photoId->toString(), $ownerId->toString()));

    expect($result->isPrimary())->toBeTrue();
});

it('rejects setting the cover for a pet the actor does not own', function (): void {
    $petId = Id::generate();
    $photoId = Id::generate();
    $pet = Pet::create($petId, Id::generate(), 1, null, 'Питомец', PetSex::Male, null, PetPurpose::Social, null, false);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);

    $handler = new SetPetPhotoCoverHandler($pets, $photos);
    $handler->handle(new SetPetPhotoCoverCommand($petId->toString(), $photoId->toString(), Id::generate()->toString()));
})->throws(PetNotOwnedByActorException::class);

it('rejects setting the cover to a photo that does not belong to the pet', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $photoId = Id::generate();
    $pet = Pet::create($petId, $ownerId, 1, null, 'Питомец', PetSex::Male, null, PetPurpose::Social, null, false);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('findById')->once()->andReturn(null);

    $handler = new SetPetPhotoCoverHandler($pets, $photos);
    $handler->handle(new SetPetPhotoCoverCommand($petId->toString(), $photoId->toString(), $ownerId->toString()));
})->throws(PetPhotoNotFoundException::class);
