<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Adapters;

use App\Modules\Media\Application\Services\UploadMediaService;
use App\Modules\Shelter\Application\Contracts\MediaUploaderInterface;
use App\Modules\Shelter\Application\Contracts\UploadedMedia;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Единственное место, где модуль Media "знает" про Shelter — реализация чужого
 * Application-контракта, байндится в MediaServiceProvider::register(), см.
 * docs/rules/01-backend.md. Дословно повторяет ProfileMediaUploader/IdentityMediaUploader —
 * тот же UploadMediaService, другой потребительский контракт.
 */
final class ShelterMediaUploader implements MediaUploaderInterface
{
    public function __construct(
        private readonly UploadMediaService $uploader,
    ) {}

    public function upload(UploadedFile $file, Id $ownerId): UploadedMedia
    {
        $media = $this->uploader->upload($file, $ownerId);
        $url = Storage::disk($media->disk())->url($media->path());

        return new UploadedMedia($media->id()->toString(), $url);
    }
}
