<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum PhoneCodePurpose: string
{
    case Login = 'login';
    case PasswordReset = 'password_reset';
}
