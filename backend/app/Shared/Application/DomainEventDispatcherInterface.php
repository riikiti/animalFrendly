<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\Shared\Domain\DomainEvent;

interface DomainEventDispatcherInterface
{
    public function dispatch(DomainEvent $event): void;
}
