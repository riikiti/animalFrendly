<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Indexing;

use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Shared\Domain\ValueObjects\Id;

final class IndexListingService
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
        private readonly BuildListingDocument $builder,
        private readonly ListingSearchIndexInterface $index,
    ) {}

    public function index(Id $listingId): void
    {
        $listing = $this->listings->findById($listingId);

        if ($listing === null) {
            $this->index->deleteDocument($listingId->toString());

            return;
        }

        $document = $this->builder->build($listing);

        if ($document === null) {
            $this->index->deleteDocument($listingId->toString());

            return;
        }

        $this->index->putDocument($document);
    }
}
