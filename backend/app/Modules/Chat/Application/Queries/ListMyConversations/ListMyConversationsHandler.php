<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Queries\ListMyConversations;

use App\Modules\Chat\Domain\Entities\Conversation;
use App\Modules\Chat\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Композиция через явные Domain-контракты нескольких модулей:
 * — мои питомцы (Profile) → их мэтчи (Matching) → беседы этих мэтчей;
 * — мои заявки на усыновление и заявки на животных из моих приютов (Shelter) → их беседы;
 * — мои исходящие прямые обращения в приюты + входящие в приюты, которыми я владею.
 * См. docs/plan/03-architecture.md.
 */
final class ListMyConversationsHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly PetMatchRepositoryInterface $matches,
        private readonly ShelterRepositoryInterface $shelters,
        private readonly ShelterAnimalRepositoryInterface $shelterAnimals,
        private readonly AdoptionRequestRepositoryInterface $adoptionRequests,
        private readonly ConversationRepositoryInterface $conversations,
    ) {}

    /**
     * @return list<Conversation>
     */
    public function handle(ListMyConversationsQuery $query): array
    {
        $userId = Id::fromString($query->actingUserId);

        /** @var array<string, Id> $matchIds */
        $matchIds = [];
        foreach ($this->pets->findByOwner($userId) as $pet) {
            foreach ($this->matches->findForPet($pet->id()) as $match) {
                $matchIds[$match->id()->toString()] = $match->id();
            }
        }

        /** @var array<string, Id> $adoptionRequestIds */
        $adoptionRequestIds = [];
        foreach ($this->adoptionRequests->findByRequester($userId) as $request) {
            $adoptionRequestIds[$request->id()->toString()] = $request->id();
        }

        $myShelterIds = [];
        foreach ($this->shelters->findByOwnerUserId($userId) as $shelter) {
            $myShelterIds[] = $shelter->id();

            foreach ($this->shelterAnimals->findByShelter($shelter->id()) as $animal) {
                foreach ($this->adoptionRequests->findByShelterAnimal($animal->id()) as $request) {
                    $adoptionRequestIds[$request->id()->toString()] = $request->id();
                }
            }
        }

        return [
            ...$this->conversations->findByMatchIds(array_values($matchIds)),
            ...$this->conversations->findByAdoptionRequestIds(array_values($adoptionRequestIds)),
            ...$this->conversations->findByInitiatorUserId($userId),
            ...$this->conversations->findByShelterIds($myShelterIds),
        ];
    }
}
