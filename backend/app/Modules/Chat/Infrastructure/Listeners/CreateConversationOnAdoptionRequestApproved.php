<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Listeners;

use App\Modules\Chat\Application\Commands\CreateConversationForAdoptionRequest\CreateConversationForAdoptionRequestCommand;
use App\Modules\Chat\Application\Commands\CreateConversationForAdoptionRequest\CreateConversationForAdoptionRequestHandler;
use App\Modules\Shelter\Domain\Events\AdoptionRequestApproved;

final class CreateConversationOnAdoptionRequestApproved
{
    public function __construct(private readonly CreateConversationForAdoptionRequestHandler $handler) {}

    public function handle(AdoptionRequestApproved $event): void
    {
        $this->handler->handle(new CreateConversationForAdoptionRequestCommand($event->adoptionRequestId->toString()));
    }
}
