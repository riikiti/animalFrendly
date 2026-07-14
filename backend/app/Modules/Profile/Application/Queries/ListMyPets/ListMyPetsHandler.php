<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Queries\ListMyPets;

use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListMyPetsHandler
{
    public function __construct(private readonly PetRepositoryInterface $pets) {}

    /**
     * @return list<Pet>
     */
    public function handle(string $ownerId): array
    {
        return $this->pets->findByOwner(Id::fromString($ownerId));
    }
}
