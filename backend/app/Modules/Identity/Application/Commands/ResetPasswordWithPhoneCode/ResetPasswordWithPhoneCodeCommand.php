<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\ResetPasswordWithPhoneCode;

final class ResetPasswordWithPhoneCodeCommand
{
    public function __construct(
        public readonly string $phone,
        public readonly string $code,
        public readonly string $password,
    ) {}
}
