<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Commands\RegisterDeviceToken;

final class RegisterDeviceTokenCommand
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $platform,
        public readonly string $fcmToken,
    ) {}
}
