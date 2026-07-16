<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Repositories;

use App\Modules\Marketplace\Domain\Entities\Breeder;
use App\Shared\Domain\ValueObjects\Id;

interface BreederRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Breeder $breeder): void;

    public function findById(Id $id): ?Breeder;

    public function findByOwnerUserId(Id $ownerUserId): ?Breeder;

    public function countPendingVerification(): int;

    /**
     * @return list<Breeder>
     */
    public function findPendingVerification(): array;
}
