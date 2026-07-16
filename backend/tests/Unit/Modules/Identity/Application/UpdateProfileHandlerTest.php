<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Commands\UpdateProfile\UpdateProfileCommand;
use App\Modules\Identity\Application\Commands\UpdateProfile\UpdateProfileHandler;
use App\Modules\Identity\Application\Contracts\GeocodedAddress;
use App\Modules\Identity\Application\Contracts\GeocoderInterface;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Exceptions\UserNotFoundException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Shared\Domain\ValueObjects\Id;

function makeProfileTestUser(Id $id): User
{
    return User::register(
        id: $id,
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed-password',
        accountType: AccountType::Owner,
        personalDataConsentGiven: true,
    );
}

it('geocodes the address and stores city/coordinates when the geocoder resolves it', function (): void {
    $userId = Id::generate();
    $user = makeProfileTestUser($userId);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::on(
        fn (User $u) => $u->city() === 'Москва' && $u->latitude() === 55.755 && $u->longitude() === 37.617,
    ));

    $geocoder = Mockery::mock(GeocoderInterface::class);
    $geocoder->shouldReceive('geocode')->once()->with('Москва, ул. Тверская, 1')->andReturn(
        new GeocodedAddress('Москва, Тверская улица, 1', 'Москва', 55.755, 37.617),
    );

    $handler = new UpdateProfileHandler($users, $geocoder);
    $handler->handle(new UpdateProfileCommand($userId->toString(), null, 'Москва, ул. Тверская, 1'));
});

it('stores the raw address without coordinates when the geocoder cannot resolve it', function (): void {
    $userId = Id::generate();
    $user = makeProfileTestUser($userId);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::on(
        fn (User $u) => $u->address() === 'Непонятный адрес' && ! $u->hasCoordinates(),
    ));

    $geocoder = Mockery::mock(GeocoderInterface::class);
    $geocoder->shouldReceive('geocode')->once()->andReturn(null);

    $handler = new UpdateProfileHandler($users, $geocoder);
    $handler->handle(new UpdateProfileCommand($userId->toString(), null, 'Непонятный адрес'));
});

it('clears the location when the address is set to null', function (): void {
    $userId = Id::generate();
    $user = makeProfileTestUser($userId);
    $user->setLocation('Старый адрес', 'Москва', 55.755, 37.617);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::on(
        fn (User $u) => $u->address() === null && $u->city() === null,
    ));

    $geocoder = Mockery::mock(GeocoderInterface::class);
    $geocoder->shouldNotReceive('geocode');

    $handler = new UpdateProfileHandler($users, $geocoder);
    $handler->handle(new UpdateProfileCommand($userId->toString(), null, null));
});

it('rejects updating the profile of a user that does not exist', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn(null);

    $geocoder = Mockery::mock(GeocoderInterface::class);

    $handler = new UpdateProfileHandler($users, $geocoder);
    $handler->handle(new UpdateProfileCommand(Id::generate()->toString(), null, 'Любой адрес'));
})->throws(UserNotFoundException::class);

it('sets the name', function (): void {
    $userId = Id::generate();
    $user = makeProfileTestUser($userId);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::on(
        fn (User $u) => $u->name() === 'Иван Иванов',
    ));

    $geocoder = Mockery::mock(GeocoderInterface::class);
    $geocoder->shouldNotReceive('geocode');

    $handler = new UpdateProfileHandler($users, $geocoder);
    $handler->handle(new UpdateProfileCommand($userId->toString(), 'Иван Иванов', null));
});

it('clears the name when given an empty string', function (): void {
    $userId = Id::generate();
    $user = makeProfileTestUser($userId);
    $user->setName('Иван Иванов');

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::on(
        fn (User $u) => $u->name() === null,
    ));

    $geocoder = Mockery::mock(GeocoderInterface::class);

    $handler = new UpdateProfileHandler($users, $geocoder);
    $handler->handle(new UpdateProfileCommand($userId->toString(), '', null));
});
