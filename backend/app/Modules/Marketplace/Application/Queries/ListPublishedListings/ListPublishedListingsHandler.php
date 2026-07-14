<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Queries\ListPublishedListings;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;

final class ListPublishedListingsHandler
{
    public function __construct(private readonly ListingRepositoryInterface $listings) {}

    /**
     * @return list<Listing>
     */
    public function handle(): array
    {
        return $this->listings->findPublished();
    }
}
