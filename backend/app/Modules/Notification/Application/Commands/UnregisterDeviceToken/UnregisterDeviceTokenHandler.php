<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Commands\UnregisterDeviceToken;

use App\Modules\Notification\Domain\Repositories\DeviceTokenRepositoryInterface;

final class UnregisterDeviceTokenHandler
{
    public function __construct(private readonly DeviceTokenRepositoryInterface $tokens) {}

    public function handle(UnregisterDeviceTokenCommand $command): void
    {
        $this->tokens->deleteByToken($command->fcmToken);
    }
}
