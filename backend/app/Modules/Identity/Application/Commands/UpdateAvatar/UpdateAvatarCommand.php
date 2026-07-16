<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Commands\UpdateAvatar;

use Illuminate\Http\UploadedFile;

final class UpdateAvatarCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly UploadedFile $photo,
    ) {}
}
