<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\AuthenticateUser;

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Exceptions\AccountBlockedException;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Hashing\Hasher;

final class AuthenticateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Hasher $hasher,
    ) {}

    public function handle(AuthenticateUserCommand $command): User
    {
        $phone = PhoneNumber::fromString($command->phone);
        $user = $this->users->findByPhone($phone);

        if ($user === null || ! $this->hasher->check($command->password, $user->passwordHash())) {
            throw InvalidCredentialsException::create();
        }

        if ($user->isBlocked()) {
            throw AccountBlockedException::create();
        }

        return $user;
    }
}
