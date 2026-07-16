<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\UpdateShelter;

final class UpdateShelterCommand
{
    public function __construct(
        public readonly string $shelterId,
        public readonly string $actingUserId,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $address,
    ) {}
}
