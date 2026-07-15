<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\UnbanUser;

final class UnbanUserCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $actingUserId,
    ) {}
}
