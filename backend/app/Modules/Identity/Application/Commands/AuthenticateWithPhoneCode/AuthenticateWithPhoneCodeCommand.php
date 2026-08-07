<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\AuthenticateWithPhoneCode;

final class AuthenticateWithPhoneCodeCommand
{
    public function __construct(
        public readonly string $phone,
        public readonly string $code,
    ) {}
}
