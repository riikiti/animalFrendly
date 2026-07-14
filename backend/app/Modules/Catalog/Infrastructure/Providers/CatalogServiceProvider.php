<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Providers;

use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Repositories\EloquentBreedRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Repositories\EloquentSpeciesRepository;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SpeciesRepositoryInterface::class, EloquentSpeciesRepository::class);
        $this->app->bind(BreedRepositoryInterface::class, EloquentBreedRepository::class);
    }
}
