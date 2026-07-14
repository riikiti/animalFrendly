<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Providers;

use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }
}
