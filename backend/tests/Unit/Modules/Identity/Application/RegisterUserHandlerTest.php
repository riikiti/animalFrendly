<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Commands\RegisterUser\RegisterUserCommand;
use App\Modules\Identity\Application\Commands\RegisterUser\RegisterUserHandler;
use App\Modules\Identity\Domain\Exceptions\PhoneAlreadyRegisteredException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Contracts\Hashing\Hasher;

it('registers a new user and persists it via the repository', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('existsByPhone')->once()->andReturn(false);
    $users->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $users->shouldReceive('save')->once();

    $hasher = Mockery::mock(Hasher::class);
    $hasher->shouldReceive('make')->once()->with('correct-password')->andReturn('hashed');

    $handler = new RegisterUserHandler($users, $hasher);

    $user = $handler->handle(new RegisterUserCommand(
        phone: '+79261234567',
        password: 'correct-password',
        accountType: 'owner',
        personalDataConsentGiven: true,
    ));

    expect($user->phone()->value())->toBe('+79261234567');
});

it('refuses to register a user whose phone is already taken', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('existsByPhone')->once()->andReturn(true);
    $users->shouldNotReceive('save');

    $hasher = Mockery::mock(Hasher::class);
    $hasher->shouldNotReceive('make');

    $handler = new RegisterUserHandler($users, $hasher);

    $handler->handle(new RegisterUserCommand(
        phone: '+79261234567',
        password: 'correct-password',
        accountType: 'owner',
        personalDataConsentGiven: true,
    ));
})->throws(PhoneAlreadyRegisteredException::class);
