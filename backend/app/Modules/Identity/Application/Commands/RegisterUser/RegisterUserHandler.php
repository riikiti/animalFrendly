<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\RegisterUser;

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Exceptions\PhoneAlreadyRegisteredException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Hashing\Hasher;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Hasher $hasher,
    ) {}

    public function handle(RegisterUserCommand $command): User
    {
        $phone = PhoneNumber::fromString($command->phone);

        if ($this->users->existsByPhone($phone)) {
            throw PhoneAlreadyRegisteredException::forPhone($phone);
        }

        $user = User::register(
            id: $this->users->nextIdentity(),
            phone: $phone,
            passwordHash: $this->hasher->make($command->password),
            accountType: AccountType::from($command->accountType),
            personalDataConsentGiven: $command->personalDataConsentGiven,
        );

        $this->users->save($user);

        return $user;
    }
}
