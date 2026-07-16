<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\UpdateShelterPhoto;

use Illuminate\Http\UploadedFile;

final class UpdateShelterPhotoCommand
{
    public function __construct(
        public readonly string $shelterId,
        public readonly string $actingUserId,
        public readonly UploadedFile $photo,
    ) {}
}
