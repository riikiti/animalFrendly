<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\GetConversationForMatch;

use App\Modules\Chat\Application\Services\MatchParticipantGuard;
use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class GetConversationForMatchHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
        private readonly MatchParticipantGuard $participantGuard,
    ) {}

    public function handle(GetConversationForMatchQuery $query): Conversation
    {
        $matchId = Id::fromString($query->matchId);
        $this->participantGuard->assertParticipant($matchId, Id::fromString($query->actingUserId));

        $conversation = $this->conversations->findByMatchId($matchId);

        if ($conversation !== null) {
            return $conversation;
        }

        // Обычно беседа уже создана listener'ом на PetsMatched — это подстраховка на
        // случай гонки/ретрая, не основной путь.
        $conversation = Conversation::createForMatch($this->conversations->nextIdentity(), $matchId);
        $this->conversations->save($conversation);

        return $conversation;
    }
}
