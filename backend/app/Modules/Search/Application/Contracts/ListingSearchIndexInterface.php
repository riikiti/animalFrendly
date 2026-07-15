<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Contracts;

use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Modules\Search\Application\Queries\SearchListings\SearchListingsQuery;

interface ListingSearchIndexInterface
{
    public function configureIndex(): void;

    /**
     * @param  array<string, mixed>  $document
     */
    public function putDocument(array $document): void;

    public function deleteDocument(string $id): void;

    public function deleteAll(): void;

    public function search(SearchListingsQuery $query, ?float $originLat, ?float $originLng): SearchResultPage;
}
