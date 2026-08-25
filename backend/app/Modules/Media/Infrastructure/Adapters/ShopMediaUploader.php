<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Adapters;

use App\Modules\Media\Application\Services\UploadMediaService;
use App\Modules\Shop\Application\Contracts\MediaUploaderInterface;
use App\Modules\Shop\Application\Contracts\UploadedMedia;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Единственное место, где модуль Media "знает" про Shop — реализация чужого
 * Application-контракта, байндится в MediaServiceProvider::register().
 * Дословно повторяет IdentityMediaUploader: тот же UploadMediaService, другой контракт.
 */
final class ShopMediaUploader implements MediaUploaderInterface
{
    public function __construct(private readonly UploadMediaService $uploader) {}

    public function upload(UploadedFile $file, Id $ownerId): UploadedMedia
    {
        $media = $this->uploader->upload($file, $ownerId);
        $url = Storage::disk($media->disk())->url($media->path());

        return new UploadedMedia($media->id()->toString(), $url);
    }
}
