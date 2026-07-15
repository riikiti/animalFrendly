<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\AddPetPhoto;

use Illuminate\Http\UploadedFile;

final class AddPetPhotoCommand
{
    public function __construct(
        public readonly string $petId,
        public readonly string $actingUserId,
        public readonly UploadedFile $photo,
    ) {}
}
