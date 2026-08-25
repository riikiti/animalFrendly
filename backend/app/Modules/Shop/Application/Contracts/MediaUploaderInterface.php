<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Contracts;

use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Http\UploadedFile;

/**
 * Контракт в сторону модуля Media — объявлен здесь (в Shop), реализуется в
 * Media\Infrastructure\Adapters\ShopMediaUploader. Тот же приём, что у Identity и Profile.
 */
interface MediaUploaderInterface
{
    public function upload(UploadedFile $file, Id $ownerId): UploadedMedia;
}
