<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;

final class LaravelDomainEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(private readonly Dispatcher $events) {}

    public function dispatch(DomainEvent $event): void
    {
        $this->events->dispatch($event);
    }
}
