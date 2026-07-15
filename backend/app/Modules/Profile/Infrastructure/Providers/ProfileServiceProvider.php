<?php

declare(strict_types=1);

namespace App\Modules\Profile\Infrastructure\Providers;

use App\Modules\Profile\Domain\Repositories\PetPhotoRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Repositories\EloquentPetPhotoRepository;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Repositories\EloquentPetRepository;
use Illuminate\Support\ServiceProvider;

final class ProfileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PetRepositoryInterface::class, EloquentPetRepository::class);
        $this->app->bind(PetPhotoRepositoryInterface::class, EloquentPetPhotoRepository::class);
    }
}
