<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\AuthenticateUser;

final class AuthenticateUserCommand
{
    public function __construct(
        public readonly string $phone,
        public readonly string $password,
    ) {}
}
