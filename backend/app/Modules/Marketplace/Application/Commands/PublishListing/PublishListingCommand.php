<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\PublishListing;

final class PublishListingCommand
{
    public function __construct(
        public readonly string $listingId,
        public readonly string $actingUserId,
    ) {}
}
