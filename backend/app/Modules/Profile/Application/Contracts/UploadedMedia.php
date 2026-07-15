<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Contracts;

final class UploadedMedia
{
    public function __construct(
        public readonly string $mediaId,
        public readonly string $url,
    ) {}
}
