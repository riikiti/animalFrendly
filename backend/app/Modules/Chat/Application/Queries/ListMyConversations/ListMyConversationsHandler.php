<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\ListMyConversations;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Композиция трёх модулей через явные Domain-контракты: мои питомцы (Profile) → их мэтчи
 * (Matching) → беседы этих мэтчей (Chat). См. docs/plan/03-architecture.md.
 */
final class ListMyConversationsHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly PetMatchRepositoryInterface $matches,
        private readonly ConversationRepositoryInterface $conversations,
    ) {}

    /**
     * @return list<Conversation>
     */
    public function handle(ListMyConversationsQuery $query): array
    {
        $ownerId = Id::fromString($query->actingUserId);

        /** @var array<string, Id> $matchIds */
        $matchIds = [];

        foreach ($this->pets->findByOwner($ownerId) as $pet) {
            foreach ($this->matches->findForPet($pet->id()) as $match) {
                $matchIds[$match->id()->toString()] = $match->id();
            }
        }

        return $this->conversations->findByMatchIds(array_values($matchIds));
    }
}
