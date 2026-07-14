<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Queries\ListMyListings;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListMyListingsHandler
{
    public function __construct(private readonly ListingRepositoryInterface $listings) {}

    /**
     * @return list<Listing>
     */
    public function handle(ListMyListingsQuery $query): array
    {
        return $this->listings->findBySeller(Id::fromString($query->sellerId));
    }
}
