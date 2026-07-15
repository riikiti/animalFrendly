<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Listeners;

use App\Modules\Marketplace\Domain\Events\ListingStatusChanged;
use App\Modules\Search\Infrastructure\Jobs\IndexListingJob;

final class ReindexListingOnListingStatusChanged
{
    public function handle(ListingStatusChanged $event): void
    {
        IndexListingJob::dispatch($event->listingId->toString());
    }
}
