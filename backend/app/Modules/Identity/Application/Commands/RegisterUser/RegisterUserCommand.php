<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\RegisterUser;

final class RegisterUserCommand
{
    public function __construct(
        public readonly string $phone,
        public readonly string $password,
        public readonly string $accountType,
        public readonly bool $personalDataConsentGiven,
    ) {}
}
