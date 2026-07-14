<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\RegisterShelter;

final class RegisterShelterCommand
{
    public function __construct(
        public readonly string $ownerUserId,
        public readonly string $legalName,
        public readonly ?string $inn,
        public readonly ?string $description,
    ) {}
}
