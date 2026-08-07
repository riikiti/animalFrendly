<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\ResetPasswordWithPhoneCode;

use App\Modules\Identity\Application\Services\PhoneCodeService;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\PhoneCodePurpose;
use App\Modules\Identity\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use Illuminate\Contracts\Hashing\Hasher;

final class ResetPasswordWithPhoneCodeHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PhoneCodeService $codes,
        private readonly Hasher $hasher,
    ) {}

    public function handle(ResetPasswordWithPhoneCodeCommand $command): User
    {
        $phone = PhoneNumber::fromString($command->phone);

        $this->codes->consume($phone, PhoneCodePurpose::PasswordReset, $command->code);

        $user = $this->users->findByPhone($phone);

        if ($user === null) {
            throw InvalidCredentialsException::create();
        }

        $user->changePassword($this->hasher->make($command->password));
        $this->users->save($user);

        // Старые токены отзываем: смена пароля должна выкидывать чужие сессии.
        $this->users->revokeAllTokens($user->id());

        return $user;
    }
}
