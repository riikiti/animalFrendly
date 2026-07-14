<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Listeners;

use App\Modules\Chat\Application\Commands\CreateConversationForMatch\CreateConversationForMatchCommand;
use App\Modules\Chat\Application\Commands\CreateConversationForMatch\CreateConversationForMatchHandler;
use App\Modules\Matching\Domain\Events\PetsMatched;

final class CreateConversationOnPetsMatched
{
    public function __construct(private readonly CreateConversationForMatchHandler $handler) {}

    public function handle(PetsMatched $event): void
    {
        $this->handler->handle(new CreateConversationForMatchCommand($event->matchId->toString()));
    }
}
