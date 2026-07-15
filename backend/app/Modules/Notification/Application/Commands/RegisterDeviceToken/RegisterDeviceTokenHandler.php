<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Commands\RegisterDeviceToken;

use App\Modules\Notification\Domain\Entities\DeviceToken;
use App\Modules\Notification\Domain\Enums\DevicePlatform;
use App\Modules\Notification\Domain\Repositories\DeviceTokenRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class RegisterDeviceTokenHandler
{
    public function __construct(private readonly DeviceTokenRepositoryInterface $tokens) {}

    public function handle(RegisterDeviceTokenCommand $command): DeviceToken
    {
        $existing = $this->tokens->findByToken($command->fcmToken);

        if ($existing !== null) {
            $existing->touch();
            $this->tokens->save($existing);

            return $existing;
        }

        $token = DeviceToken::create(
            id: $this->tokens->nextIdentity(),
            userId: Id::fromString($command->actingUserId),
            platform: DevicePlatform::from($command->platform),
            fcmToken: $command->fcmToken,
        );
        $this->tokens->save($token);

        return $token;
    }
}
