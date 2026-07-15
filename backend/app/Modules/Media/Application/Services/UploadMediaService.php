<?php

declare(strict_types=1);

namespace App\Modules\Media\Application\Services;

use App\Modules\Media\Domain\Entities\Media;
use App\Modules\Media\Domain\Exceptions\InvalidMediaUploadException;
use App\Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class UploadMediaService
{
    public function __construct(
        private readonly MediaRepositoryInterface $media,
    ) {}

    public function upload(UploadedFile $file, Id $ownerUserId): Media
    {
        $mimeType = (string) $file->getMimeType();
        $allowedMimeTypes = config('media.allowed_mime_types');

        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            throw InvalidMediaUploadException::unsupportedType($mimeType);
        }

        $maxSizeKb = (int) config('media.max_size_kb');

        if ($file->getSize() > $maxSizeKb * 1024) {
            throw InvalidMediaUploadException::tooLarge($maxSizeKb);
        }

        $disk = (string) config('media.disk');
        $filename = Str::uuid()->toString().'.'.$file->extension();
        $path = $file->storeAs("uploads/{$ownerUserId->toString()}", $filename, $disk);

        if ($path === false) {
            throw new \RuntimeException('Не удалось сохранить файл.');
        }

        $media = Media::create(
            id: $this->media->nextIdentity(),
            ownerUserId: $ownerUserId,
            disk: $disk,
            path: $path,
            mimeType: $mimeType,
            sizeBytes: $file->getSize(),
        );

        $this->media->save($media);

        return $media;
    }
}
