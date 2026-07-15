<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Providers;

use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Infrastructure\Adapters\NotificationUserEmailLookup;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use App\Modules\Notification\Application\Contracts\UserEmailLookupInterface;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);

        // Единственное место, где Identity "знает" про Notification — байндинг чужого
        // Application-контракта, см. docs/rules/01-backend.md.
        $this->app->bind(UserEmailLookupInterface::class, NotificationUserEmailLookup::class);
    }
}
