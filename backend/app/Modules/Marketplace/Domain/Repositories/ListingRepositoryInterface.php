<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Repositories;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Shared\Domain\ValueObjects\Id;

interface ListingRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Listing $listing): void;

    public function findById(Id $id): ?Listing;

    /**
     * @return list<Listing>
     */
    public function findPublished(): array;

    /**
     * @return list<Listing>
     */
    public function findBySeller(Id $sellerId): array;
}
