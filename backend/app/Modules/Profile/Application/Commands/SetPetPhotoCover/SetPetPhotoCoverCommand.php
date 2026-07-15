<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\SetPetPhotoCover;

final class SetPetPhotoCoverCommand
{
    public function __construct(
        public readonly string $petId,
        public readonly string $photoId,
        public readonly string $actingUserId,
    ) {}
}
