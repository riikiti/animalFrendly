<?php

declare(strict_types=1);

namespace App\Modules\Profile\Domain\Repositories;

use App\Modules\Profile\Domain\Entities\Pet;
use App\Shared\Domain\ValueObjects\Id;

interface PetRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Pet $pet): void;

    public function findById(Id $id): ?Pet;

    /**
     * @return list<Pet>
     */
    public function findByOwner(Id $ownerId): array;
}
