<?php

declare(strict_types=1);

namespace App\Modules\Matching\Infrastructure\Providers;

use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Matching\Domain\Repositories\SwipeRepositoryInterface;
use App\Modules\Matching\Infrastructure\Persistence\Eloquent\Repositories\EloquentPetMatchRepository;
use App\Modules\Matching\Infrastructure\Persistence\Eloquent\Repositories\EloquentSwipeRepository;
use Illuminate\Support\ServiceProvider;

final class MatchingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SwipeRepositoryInterface::class, EloquentSwipeRepository::class);
        $this->app->bind(PetMatchRepositoryInterface::class, EloquentPetMatchRepository::class);
    }
}
