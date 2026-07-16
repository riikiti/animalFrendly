<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Providers;

use App\Modules\Identity\Application\Contracts\MediaUploaderInterface as IdentityMediaUploaderInterface;
use App\Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use App\Modules\Media\Infrastructure\Adapters\IdentityMediaUploader;
use App\Modules\Media\Infrastructure\Adapters\ProfileMediaUploader;
use App\Modules\Media\Infrastructure\Adapters\ShelterMediaUploader;
use App\Modules\Media\Infrastructure\Persistence\Eloquent\Repositories\EloquentMediaRepository;
use App\Modules\Profile\Application\Contracts\MediaUploaderInterface;
use App\Modules\Shelter\Application\Contracts\MediaUploaderInterface as ShelterMediaUploaderInterface;
use Illuminate\Support\ServiceProvider;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaRepositoryInterface::class, EloquentMediaRepository::class);

        // Единственное место, где Media "знает" про Profile/Identity/Shelter — байндинг
        // чужих Application-контрактов, см. docs/rules/01-backend.md.
        $this->app->bind(MediaUploaderInterface::class, ProfileMediaUploader::class);
        $this->app->bind(IdentityMediaUploaderInterface::class, IdentityMediaUploader::class);
        $this->app->bind(ShelterMediaUploaderInterface::class, ShelterMediaUploader::class);
    }
}
