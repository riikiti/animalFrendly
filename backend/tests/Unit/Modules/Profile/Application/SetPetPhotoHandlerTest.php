<?php

declare(strict_types=1);

use App\Modules\Profile\Application\Commands\SetPetPhoto\SetPetPhotoCommand;
use App\Modules\Profile\Application\Commands\SetPetPhoto\SetPetPhotoHandler;
use App\Modules\Profile\Application\Contracts\MediaUploaderInterface;
use App\Modules\Profile\Application\Contracts\UploadedMedia;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;

function makePhotoTestPet(Id $id, Id $ownerId): Pet
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

it('sets the pet photo via the media uploader', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $pet = makePhotoTestPet($petId, $ownerId);
    $mediaId = Id::generate();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldReceive('save')->once()->with(Mockery::on(fn (Pet $p) => $p->photoUrl() === 'https://cdn.example/photo.jpg'));

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldReceive('upload')->once()
        ->andReturn(new UploadedMedia($mediaId->toString(), 'https://cdn.example/photo.jpg'));

    $handler = new SetPetPhotoHandler($pets, $uploader);
    $result = $handler->handle(new SetPetPhotoCommand(
        petId: $petId->toString(),
        actingUserId: $ownerId->toString(),
        photo: UploadedFile::fake()->image('cat.jpg'),
    ));

    expect($result->photoUrl())->toBe('https://cdn.example/photo.jpg')
        ->and($result->photoMediaId()?->equals($mediaId))->toBeTrue();
});

it('rejects setting the photo for a pet the actor does not own', function (): void {
    $petId = Id::generate();
    $pet = makePhotoTestPet($petId, Id::generate());

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldNotReceive('save');

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldNotReceive('upload');

    $handler = new SetPetPhotoHandler($pets, $uploader);
    $handler->handle(new SetPetPhotoCommand(
        petId: $petId->toString(),
        actingUserId: Id::generate()->toString(),
        photo: UploadedFile::fake()->image('cat.jpg'),
    ));
})->throws(PetNotOwnedByActorException::class);
