<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\PurchaseListing;

final class PurchaseListingCommand
{
    public function __construct(
        public readonly string $listingId,
        public readonly string $buyerId,
    ) {}
}
