<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Queries\ListMyListings;

final class ListMyListingsQuery
{
    public function __construct(public readonly string $sellerId) {}
}
