<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Queries\ListCandidates;

use App\Modules\Matching\Domain\Exceptions\PetNotFoundException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Matching\Domain\Repositories\SwipeRepositoryInterface;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListCandidatesHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly SwipeRepositoryInterface $swipes,
    ) {}

    /**
     * @return list<Pet>
     */
    public function handle(ListCandidatesQuery $query): array
    {
        $swiperPetId = Id::fromString($query->swiperPetId);
        $actingUserId = Id::fromString($query->actingUserId);

        $swiperPet = $this->pets->findById($swiperPetId);

        if ($swiperPet === null) {
            throw PetNotFoundException::forId($query->swiperPetId);
        }

        if (! $swiperPet->ownerId()->equals($actingUserId)) {
            throw PetNotOwnedByActorException::create();
        }

        $excludeIds = [...$this->swipes->swipedTargetIds($swiperPetId), $swiperPetId];

        return $this->pets->findActiveExcluding($actingUserId, $excludeIds, $query->limit);
    }
}
