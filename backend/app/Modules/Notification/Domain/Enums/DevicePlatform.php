<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Enums;

enum DevicePlatform: string
{
    case Android = 'android';
    case Ios = 'ios';
}
