<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Queries\ListMatches;

use App\Modules\Matching\Domain\Entities\PetMatch;
use App\Modules\Matching\Domain\Exceptions\PetNotFoundException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListMatchesHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly PetMatchRepositoryInterface $matches,
    ) {}

    /**
     * @return list<PetMatch>
     */
    public function handle(ListMatchesQuery $query): array
    {
        $petId = Id::fromString($query->petId);
        $pet = $this->pets->findById($petId);

        if ($pet === null) {
            throw PetNotFoundException::forId($query->petId);
        }

        if (! $pet->ownerId()->equals(Id::fromString($query->actingUserId))) {
            throw PetNotOwnedByActorException::create();
        }

        return $this->matches->findForPet($petId);
    }
}
