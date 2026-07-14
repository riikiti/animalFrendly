<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Infrastructure\LaravelDomainEventDispatcher;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DomainEventDispatcherInterface::class, LaravelDomainEventDispatcher::class);
    }
}
