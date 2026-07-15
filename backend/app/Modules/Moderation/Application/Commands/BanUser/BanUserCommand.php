<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\BanUser;

final class BanUserCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $actingUserId,
    ) {}
}
