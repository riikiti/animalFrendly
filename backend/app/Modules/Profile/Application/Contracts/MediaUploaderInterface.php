<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;

interface MediaUploaderInterface
{
    public function upload(UploadedFile $file, Id $ownerId): UploadedMedia;
}
