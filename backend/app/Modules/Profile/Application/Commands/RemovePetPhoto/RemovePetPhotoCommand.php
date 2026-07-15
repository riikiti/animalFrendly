<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\RemovePetPhoto;

final class RemovePetPhotoCommand
{
    public function __construct(
        public readonly string $petId,
        public readonly string $actingUserId,
    ) {}
}
