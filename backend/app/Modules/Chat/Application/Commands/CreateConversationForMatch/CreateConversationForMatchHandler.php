<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Commands\CreateConversationForMatch;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class CreateConversationForMatchHandler
{
    public function __construct(private readonly ConversationRepositoryInterface $conversations) {}

    public function handle(CreateConversationForMatchCommand $command): Conversation
    {
        $matchId = Id::fromString($command->matchId);

        $existing = $this->conversations->findByMatchId($matchId);

        if ($existing !== null) {
            return $existing;
        }

        $conversation = Conversation::createForMatch($this->conversations->nextIdentity(), $matchId);
        $this->conversations->save($conversation);

        return $conversation;
    }
}
