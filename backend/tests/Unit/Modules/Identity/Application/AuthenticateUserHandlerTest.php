<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserCommand;
use App\Modules\Identity\Application\Commands\AuthenticateUser\AuthenticateUserHandler;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Contracts\Hashing\Hasher;

it('authenticates a user with correct credentials', function (): void {
    $existingUser = User::register(
        id: Id::generate(),
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed',
        accountType: AccountType::Owner,
        personalDataConsentGiven: true,
    );

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findByPhone')->once()->andReturn($existingUser);

    $hasher = Mockery::mock(Hasher::class);
    $hasher->shouldReceive('check')->once()->with('correct-password', 'hashed')->andReturn(true);

    $handler = new AuthenticateUserHandler($users, $hasher);

    $user = $handler->handle(new AuthenticateUserCommand('+79261234567', 'correct-password'));

    expect($user->id()->equals($existingUser->id()))->toBeTrue();
});

it('rejects authentication when the user does not exist', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findByPhone')->once()->andReturn(null);

    $hasher = Mockery::mock(Hasher::class);
    $hasher->shouldNotReceive('check');

    $handler = new AuthenticateUserHandler($users, $hasher);

    $handler->handle(new AuthenticateUserCommand('+79261234567', 'whatever'));
})->throws(InvalidCredentialsException::class);

it('rejects authentication when the password is wrong', function (): void {
    $existingUser = User::register(
        id: Id::generate(),
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed',
        accountType: AccountType::Owner,
        personalDataConsentGiven: true,
    );

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findByPhone')->once()->andReturn($existingUser);

    $hasher = Mockery::mock(Hasher::class);
    $hasher->shouldReceive('check')->once()->andReturn(false);

    $handler = new AuthenticateUserHandler($users, $hasher);

    $handler->handle(new AuthenticateUserCommand('+79261234567', 'wrong-password'));
})->throws(InvalidCredentialsException::class);
