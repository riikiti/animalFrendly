<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Services;

use App\Modules\Chat\Domain\Exceptions\ConversationAccessDeniedException;
use App\Modules\Chat\Domain\Exceptions\MatchNotFoundException;
use App\Modules\Matching\Domain\Entities\PetMatch;
use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Явный межмодульный контракт: Chat опирается на Domain-репозитории Matching и Profile,
 * а не на их Eloquent-модели — см. docs/plan/03-architecture.md.
 */
final class MatchParticipantGuard
{
    public function __construct(
        private readonly PetMatchRepositoryInterface $matches,
        private readonly PetRepositoryInterface $pets,
    ) {}

    public function assertParticipant(Id $matchId, Id $actingUserId): PetMatch
    {
        $match = $this->matches->findById($matchId);

        if ($match === null) {
            throw MatchNotFoundException::forId($matchId->toString());
        }

        $petA = $this->pets->findById($match->petAId());
        $petB = $this->pets->findById($match->petBId());

        $isParticipant = ($petA?->ownerId()->equals($actingUserId) ?? false)
            || ($petB?->ownerId()->equals($actingUserId) ?? false);

        if (! $isParticipant) {
            throw ConversationAccessDeniedException::create();
        }

        return $match;
    }

    public function otherPetOwnerId(PetMatch $match, Id $senderId): ?Id
    {
        $petA = $this->pets->findById($match->petAId());
        $petB = $this->pets->findById($match->petBId());

        if ($petA !== null && ! $petA->ownerId()->equals($senderId)) {
            return $petA->ownerId();
        }

        if ($petB !== null && ! $petB->ownerId()->equals($senderId)) {
            return $petB->ownerId();
        }

        return null;
    }
}
