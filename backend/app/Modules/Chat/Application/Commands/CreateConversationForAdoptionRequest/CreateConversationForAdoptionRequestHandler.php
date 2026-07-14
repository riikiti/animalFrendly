<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\CreateConversationForAdoptionRequest;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class CreateConversationForAdoptionRequestHandler
{
    public function __construct(private readonly ConversationRepositoryInterface $conversations) {}

    public function handle(CreateConversationForAdoptionRequestCommand $command): Conversation
    {
        $adoptionRequestId = Id::fromString($command->adoptionRequestId);

        $existing = $this->conversations->findByAdoptionRequestId($adoptionRequestId);

        if ($existing !== null) {
            return $existing;
        }

        $conversation = Conversation::createForAdoptionRequest($this->conversations->nextIdentity(), $adoptionRequestId);
        $this->conversations->save($conversation);

        return $conversation;
    }
}
