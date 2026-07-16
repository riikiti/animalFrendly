<?php

declare(strict_types=1);

use App\Modules\Shelter\Application\Commands\UpdateShelterPhoto\UpdateShelterPhotoCommand;
use App\Modules\Shelter\Application\Commands\UpdateShelterPhoto\UpdateShelterPhotoHandler;
use App\Modules\Shelter\Application\Contracts\MediaUploaderInterface;
use App\Modules\Shelter\Application\Contracts\UploadedMedia;
use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;

it('uploads and sets the shelter photo', function (): void {
    $shelterId = Id::generate();
    $ownerId = Id::generate();
    $shelter = Shelter::register($shelterId, $ownerId, 'Добрые лапы', null, null);

    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn($shelter);
    $shelters->shouldReceive('save')->once()->with(Mockery::on(
        fn (Shelter $s) => $s->photoUrl() === 'https://cdn.example/shelter.jpg',
    ));

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldReceive('upload')->once()
        ->andReturn(new UploadedMedia(Id::generate()->toString(), 'https://cdn.example/shelter.jpg'));

    $handler = new UpdateShelterPhotoHandler($shelters, $uploader);
    $handler->handle(new UpdateShelterPhotoCommand(
        shelterId: $shelterId->toString(),
        actingUserId: $ownerId->toString(),
        photo: UploadedFile::fake()->image('shelter.jpg'),
    ));
});

it('rejects a shelter photo upload from a non-owner', function (): void {
    $shelterId = Id::generate();
    $shelter = Shelter::register($shelterId, Id::generate(), 'Добрые лапы', null, null);

    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn($shelter);
    $shelters->shouldNotReceive('save');

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldNotReceive('upload');

    $handler = new UpdateShelterPhotoHandler($shelters, $uploader);
    $handler->handle(new UpdateShelterPhotoCommand(
        shelterId: $shelterId->toString(),
        actingUserId: Id::generate()->toString(),
        photo: UploadedFile::fake()->image('shelter.jpg'),
    ));
})->throws(NotShelterOwnerException::class);

it('rejects a photo upload for a shelter that does not exist', function (): void {
    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn(null);

    $uploader = Mockery::mock(MediaUploaderInterface::class);
    $uploader->shouldNotReceive('upload');

    $handler = new UpdateShelterPhotoHandler($shelters, $uploader);
    $handler->handle(new UpdateShelterPhotoCommand(
        shelterId: Id::generate()->toString(),
        actingUserId: Id::generate()->toString(),
        photo: UploadedFile::fake()->image('shelter.jpg'),
    ));
})->throws(ShelterNotFoundException::class);
