<?php

declare(strict_types=1);

use App\Modules\Profile\Application\Commands\AddPetPhoto\AddPetPhotoCommand;
use App\Modules\Profile\Application\Commands\AddPetPhoto\AddPetPhotoHandler;
use App\Modules\Profile\Application\Contracts\MediaUploaderInterface;
use App\Modules\Profile\Application\Contracts\UploadedMedia;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Entities\PetPhoto;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Exceptions\TooManyPetPhotosException;
use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;

function makeGalleryTestPet(Id $id, Id $ownerId): Pet
{
    return Pet::create(
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
}

it('adds the first photo and makes it the cover', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $pet = makeGalleryTestPet($petId, $ownerId);
    $mediaId = Id::generate();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldReceive('save')->once()->with(Mockery::on(fn (Pet $p) => $p->photoUrl() === 'https://cdn.example/photo.jpg'));

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('countForPet')->once()->andReturn(0);
    $photos->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $photos->shouldReceive('save')->once()->with(Mockery::on(fn (PetPhoto $p) => $p->isPrimary() === true));

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldReceive('upload')->once()
        ->andReturn(new UploadedMedia($mediaId->toString(), 'https://cdn.example/photo.jpg'));

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new AddPetPhotoHandler($pets, $photos, $uploader, $events);
    $result = $handler->handle(new AddPetPhotoCommand(
        petId: $petId->toString(),
        actingUserId: $ownerId->toString(),
        photo: UploadedFile::fake()->image('cat.jpg'),
    ));

    expect($result->isPrimary())->toBeTrue()
        ->and($result->url())->toBe('https://cdn.example/photo.jpg');
});

it('adds a second photo without changing the existing cover', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $pet = makeGalleryTestPet($petId, $ownerId);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldNotReceive('save');

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('countForPet')->once()->andReturn(1);
    $photos->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $photos->shouldReceive('save')->once()->with(Mockery::on(fn (PetPhoto $p) => $p->isPrimary() === false));

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldReceive('upload')->once()
        ->andReturn(new UploadedMedia(Id::generate()->toString(), 'https://cdn.example/second.jpg'));

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldNotReceive('dispatch');

    $handler = new AddPetPhotoHandler($pets, $photos, $uploader, $events);
    $result = $handler->handle(new AddPetPhotoCommand(
        petId: $petId->toString(),
        actingUserId: $ownerId->toString(),
        photo: UploadedFile::fake()->image('cat.jpg'),
    ));

    expect($result->isPrimary())->toBeFalse();
});

it('rejects adding a photo once the limit is reached', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $pet = makeGalleryTestPet($petId, $ownerId);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $photos->shouldReceive('countForPet')->once()->andReturn(AddPetPhotoHandler::MAX_PHOTOS_PER_PET);
    $photos->shouldNotReceive('save');

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldNotReceive('upload');

    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new AddPetPhotoHandler($pets, $photos, $uploader, $events);
    $handler->handle(new AddPetPhotoCommand(
        petId: $petId->toString(),
        actingUserId: $ownerId->toString(),
        photo: UploadedFile::fake()->image('cat.jpg'),
    ));
})->throws(TooManyPetPhotosException::class);

it('rejects adding a photo for a pet the actor does not own', function (): void {
    $petId = Id::generate();
    $pet = makeGalleryTestPet($petId, Id::generate());

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);

    $photos = Mockery::mock(PetPhotoRepositoryInterface::class);
    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new AddPetPhotoHandler($pets, $photos, $uploader, $events);
    $handler->handle(new AddPetPhotoCommand(
        petId: $petId->toString(),
        actingUserId: Id::generate()->toString(),
        photo: UploadedFile::fake()->image('cat.jpg'),
    ));
})->throws(PetNotOwnedByActorException::class);
