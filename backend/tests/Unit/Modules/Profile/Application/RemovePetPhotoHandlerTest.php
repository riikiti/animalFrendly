<?php

declare(strict_types=1);

use App\Modules\Profile\Application\Commands\RemovePetPhoto\RemovePetPhotoCommand;
use App\Modules\Profile\Application\Commands\RemovePetPhoto\RemovePetPhotoHandler;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Enums\PetStatus;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

it('removes the pet photo', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $pet = Pet::reconstitute(
        id: $petId,
        ownerId: $ownerId,
        speciesId: 1,
        breedId: null,
        name: 'Питомец',
        sex: PetSex::Male,
        birthdate: null,
        purpose: PetPurpose::Social,
        description: null,
        isVaccinated: false,
        status: PetStatus::Active,
        boostedUntil: null,
        photoMediaId: Id::generate(),
        photoUrl: 'https://cdn.example/photo.jpg',
    );

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldReceive('save')->once()->with(Mockery::on(fn (Pet $p) => $p->photoUrl() === null));

    $handler = new RemovePetPhotoHandler($pets);
    $result = $handler->handle(new RemovePetPhotoCommand($petId->toString(), $ownerId->toString()));

    expect($result->photoUrl())->toBeNull();
});

it('rejects removing the photo for a pet the actor does not own', function (): void {
    $petId = Id::generate();
    $pet = Pet::create(
        id: $petId,
        ownerId: Id::generate(),
        speciesId: 1,
        breedId: null,
        name: 'Питомец',
        sex: PetSex::Male,
        birthdate: null,
        purpose: PetPurpose::Social,
        description: null,
        isVaccinated: false,
    );

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldNotReceive('save');

    $handler = new RemovePetPhotoHandler($pets);
    $handler->handle(new RemovePetPhotoCommand($petId->toString(), Id::generate()->toString()));
})->throws(PetNotOwnedByActorException::class);
