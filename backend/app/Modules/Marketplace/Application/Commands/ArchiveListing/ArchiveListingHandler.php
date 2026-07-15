<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\ArchiveListing;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Events\ListingStatusChanged;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotFoundException;
use App\Modules\Marketplace\Domain\Exceptions\NotListingOwnerException;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class ArchiveListingHandler
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

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
        $this->events->dispatch(new ListingStatusChanged($listing->id(), $listing->status(), new DateTimeImmutable));

        return $listing;
    }
}
