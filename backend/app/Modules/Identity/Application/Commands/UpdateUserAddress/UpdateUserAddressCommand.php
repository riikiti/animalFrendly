<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\UpdateUserAddress;

final class UpdateUserAddressCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $address,
    ) {}
}
