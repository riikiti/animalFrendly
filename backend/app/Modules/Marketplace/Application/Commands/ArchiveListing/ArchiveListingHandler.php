<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\ArchiveListing;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotFoundException;
use App\Modules\Marketplace\Domain\Exceptions\NotListingOwnerException;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ArchiveListingHandler
{
    public function __construct(private readonly ListingRepositoryInterface $listings) {}

    public function handle(ArchiveListingCommand $command): Listing
    {
        $listing = $this->listings->findById(Id::fromString($command->listingId));

        if ($listing === null) {
            throw ListingNotFoundException::forId($command->listingId);
        }

        if (! $listing->sellerId()->equals(Id::fromString($command->actingUserId))) {
            throw NotListingOwnerException::create();
        }

        $listing->archive();
        $this->listings->save($listing);

        return $listing;
    }
}
