<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\SetPetPhoto;

use Illuminate\Http\UploadedFile;

final class SetPetPhotoCommand
{
    public function __construct(
        public readonly string $petId,
        public readonly string $actingUserId,
        public readonly UploadedFile $photo,
    ) {}
}
