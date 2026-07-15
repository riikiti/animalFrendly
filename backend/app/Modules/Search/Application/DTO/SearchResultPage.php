<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\DTO;

final class SearchResultPage
{
    /**
     * @param  list<PetSearchResult|ListingSearchResult>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {}
}
