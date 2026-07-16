<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Contracts;

final class UploadedMedia
{
    public function __construct(
        public readonly string $mediaId,
        public readonly string $url,
    ) {}
}
