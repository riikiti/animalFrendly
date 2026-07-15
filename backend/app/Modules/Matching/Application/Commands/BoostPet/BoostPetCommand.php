<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Commands\BoostPet;

final class BoostPetCommand
{
    public function __construct(
        public readonly string $petId,
        public readonly string $actingUserId,
    ) {}
}
