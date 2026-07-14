<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\ArchiveListing;

final class ArchiveListingCommand
{
    public function __construct(
        public readonly string $listingId,
        public readonly string $actingUserId,
    ) {}
}
