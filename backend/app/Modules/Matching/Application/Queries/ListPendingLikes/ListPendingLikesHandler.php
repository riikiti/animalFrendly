<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Queries\ListPendingLikes;

use App\Modules\Matching\Domain\Exceptions\PetNotFoundException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Matching\Domain\Repositories\SwipeRepositoryInterface;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListPendingLikesHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly SwipeRepositoryInterface $swipes,
    ) {}

    public function handle(ListPendingLikesQuery $query): ListPendingLikesResult
    {
        $petId = Id::fromString($query->petId);
        $actingUserId = Id::fromString($query->actingUserId);

        $pet = $this->pets->findById($petId);

        if ($pet === null) {
            throw PetNotFoundException::forId($query->petId);
        }

        if (! $pet->ownerId()->equals($actingUserId)) {
            throw PetNotOwnedByActorException::create();
        }

        return new ListPendingLikesResult(
            received: $this->resolvePets($this->swipes->pendingIncomingLikes($petId)),
            sent: $this->resolvePets($this->swipes->pendingOutgoingLikes($petId)),
        );
    }

    /**
     * @param  list<Id>  $ids
     * @return list<Pet>
     */
    private function resolvePets(array $ids): array
    {
        return array_values(array_filter(array_map(
            fn (Id $id): ?Pet => $this->pets->findById($id),
            $ids,
        )));
    }
}
