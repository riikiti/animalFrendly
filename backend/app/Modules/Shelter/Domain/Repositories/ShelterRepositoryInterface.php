<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Domain\Repositories;

use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Shared\Domain\ValueObjects\Id;

interface ShelterRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Shelter $shelter): void;

    public function findById(Id $id): ?Shelter;

    /**
     * @return list<Shelter>
     */
    public function findByOwnerUserId(Id $ownerUserId): array;

    public function countPendingVerification(): int;
}
