<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\UpdateProfile;

final class UpdateProfileCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $name,
        public readonly ?string $address,
    ) {}
}
