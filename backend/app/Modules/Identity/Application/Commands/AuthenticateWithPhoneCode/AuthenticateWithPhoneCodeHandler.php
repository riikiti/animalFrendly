<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\AuthenticateWithPhoneCode;

use App\Modules\Identity\Application\Services\PhoneCodeService;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\PhoneCodePurpose;
use App\Modules\Identity\Domain\Exceptions\AccountBlockedException;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;

final class AuthenticateWithPhoneCodeHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PhoneCodeService $codes,
    ) {}

    public function handle(AuthenticateWithPhoneCodeCommand $command): User
    {
        $phone = PhoneNumber::fromString($command->phone);

        // Код гасим до проверки существования пользователя: иначе перебором кодов можно
        // было бы выяснять, какие номера зарегистрированы.
        $this->codes->consume($phone, PhoneCodePurpose::Login, $command->code);

        $user = $this->users->findByPhone($phone);

        if ($user === null) {
            throw InvalidCredentialsException::create();
        }

        if ($user->isBlocked()) {
            throw AccountBlockedException::create();
        }

        return $user;
    }
}
