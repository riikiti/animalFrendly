<?php

declare(strict_types=1);

use App\Modules\Shelter\Application\Commands\UpdateShelter\UpdateShelterCommand;
use App\Modules\Shelter\Application\Commands\UpdateShelter\UpdateShelterHandler;
use App\Modules\Shelter\Application\Contracts\GeocodedAddress;
use App\Modules\Shelter\Application\Contracts\GeocoderInterface;
use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Exceptions\ShelterNotFoundException;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

function makeUpdateShelterTestShelter(Id $id, Id $ownerId): Shelter
{
    return Shelter::register($id, $ownerId, 'Добрые лапы', null, null);
}

it('geocodes the address and updates contact info', function (): void {
    $shelterId = Id::generate();
    $ownerId = Id::generate();
    $shelter = makeUpdateShelterTestShelter($shelterId, $ownerId);

    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn($shelter);
    $shelters->shouldReceive('save')->once()->with(Mockery::on(
        fn (Shelter $s) => $s->phone() === '+79261234567'
            && $s->email() === 'shelter@example.com'
            && $s->city() === 'Москва',
    ));

    $geocoder = Mockery::mock(GeocoderInterface::class);
    $geocoder->shouldReceive('geocode')->once()->with('Москва, ул. Тверская, 1')->andReturn(
        new GeocodedAddress('Москва, Тверская улица, 1', 'Москва', 55.755, 37.617),
    );

    $handler = new UpdateShelterHandler($shelters, $geocoder);
    $handler->handle(new UpdateShelterCommand(
        shelterId: $shelterId->toString(),
        actingUserId: $ownerId->toString(),
        phone: '+79261234567',
        email: 'shelter@example.com',
        address: 'Москва, ул. Тверская, 1',
    ));
});

it('clears the location when the address is set to null', function (): void {
    $shelterId = Id::generate();
    $ownerId = Id::generate();
    $shelter = makeUpdateShelterTestShelter($shelterId, $ownerId);
    $shelter->setLocation('Старый адрес', 'Москва', 55.755, 37.617);

    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn($shelter);
    $shelters->shouldReceive('save')->once()->with(Mockery::on(
        fn (Shelter $s) => $s->address() === null && $s->city() === null,
    ));

    $geocoder = Mockery::mock(GeocoderInterface::class);
    $geocoder->shouldNotReceive('geocode');

    $handler = new UpdateShelterHandler($shelters, $geocoder);
    $handler->handle(new UpdateShelterCommand($shelterId->toString(), $ownerId->toString(), null, null, null));
});

it('rejects updating a shelter that does not exist', function (): void {
    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn(null);

    $geocoder = Mockery::mock(GeocoderInterface::class);

    $handler = new UpdateShelterHandler($shelters, $geocoder);
    $handler->handle(new UpdateShelterCommand(Id::generate()->toString(), Id::generate()->toString(), null, null, null));
})->throws(ShelterNotFoundException::class);

it('rejects updating a shelter the actor does not own', function (): void {
    $shelterId = Id::generate();
    $shelter = makeUpdateShelterTestShelter($shelterId, Id::generate());

    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn($shelter);
    $shelters->shouldNotReceive('save');

    $geocoder = Mockery::mock(GeocoderInterface::class);

    $handler = new UpdateShelterHandler($shelters, $geocoder);
    $handler->handle(new UpdateShelterCommand($shelterId->toString(), Id::generate()->toString(), null, null, null));
})->throws(NotShelterOwnerException::class);
