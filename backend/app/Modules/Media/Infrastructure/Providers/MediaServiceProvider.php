<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Providers;

use App\Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use App\Modules\Media\Infrastructure\Adapters\ProfileMediaUploader;
use App\Modules\Media\Infrastructure\Persistence\Eloquent\Repositories\EloquentMediaRepository;
use App\Modules\Profile\Application\Contracts\MediaUploaderInterface;
use Illuminate\Support\ServiceProvider;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaRepositoryInterface::class, EloquentMediaRepository::class);

        // Единственное место, где Media "знает" про Profile — байндинг чужого
        // Application-контракта, см. docs/rules/01-backend.md.
        $this->app->bind(MediaUploaderInterface::class, ProfileMediaUploader::class);
    }
}
