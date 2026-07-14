<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Infrastructure\Providers;

use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Repositories\EloquentAdoptionRequestRepository;
use App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Repositories\EloquentShelterAnimalRepository;
use App\Modules\Shelter\Infrastructure\Persistence\Eloquent\Repositories\EloquentShelterRepository;
use Illuminate\Support\ServiceProvider;

final class ShelterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShelterRepositoryInterface::class, EloquentShelterRepository::class);
        $this->app->bind(ShelterAnimalRepositoryInterface::class, EloquentShelterAnimalRepository::class);
        $this->app->bind(AdoptionRequestRepositoryInterface::class, EloquentAdoptionRequestRepository::class);
    }
}
