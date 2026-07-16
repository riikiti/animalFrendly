<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Adapters;

use App\Modules\Identity\Application\Contracts\MediaUploaderInterface;
use App\Modules\Identity\Application\Contracts\UploadedMedia;
use App\Modules\Media\Application\Services\UploadMediaService;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Единственное место, где модуль Media "знает" про Identity — реализация чужого
 * Application-контракта, байндится в MediaServiceProvider::register(), см.
 * docs/rules/01-backend.md. Дословно повторяет ProfileMediaUploader — тот же
 * UploadMediaService, другой потребительский контракт.
 */
final class IdentityMediaUploader implements MediaUploaderInterface
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
