<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Repositories;

use App\Modules\Matching\Domain\Entities\PetMatch;
use App\Shared\Domain\ValueObjects\Id;

interface PetMatchRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(PetMatch $match): void;

    public function existsBetween(Id $petOneId, Id $petTwoId): bool;

    /**
     * @return list<PetMatch>
     */
    public function findForPet(Id $petId): array;

    public function findById(Id $id): ?PetMatch;
}
