<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\CreateListing;

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Profile\Domain\Entities\Pet;

final class CreateListingResult
{
    public function __construct(
        public readonly Listing $listing,
        public readonly Pet $pet,
    ) {}
}
