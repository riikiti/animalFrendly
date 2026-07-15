<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Search;

use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Modules\Search\Application\DTO\ListingSearchResult;
use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Modules\Search\Application\Queries\SearchListings\SearchListingsQuery;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;

final class MeilisearchListingIndex implements ListingSearchIndexInterface
{
    public function __construct(private readonly Client $client) {}

    public function configureIndex(): void
    {
        $index = $this->index();
        $index->updateFilterableAttributes(['species_id', 'breed_id', 'city', 'price_amount', '_geo']);
        $index->updateSortableAttributes(['price_amount', '_geo']);
        $index->updateSearchableAttributes(['pet_name', 'species_name', 'breed_name']);
    }

    public function putDocument(array $document): void
    {
        $this->index()->addDocuments([$document], 'id');
    }

    public function deleteDocument(string $id): void
    {
        $this->index()->deleteDocument($id);
    }

    public function deleteAll(): void
    {
        $this->index()->deleteAllDocuments();
    }

    public function search(SearchListingsQuery $query, ?float $originLat, ?float $originLng): SearchResultPage
    {
        $filters = [];

        if ($query->speciesId !== null) {
            $filters[] = "species_id = {$query->speciesId}";
        }

        if ($query->breedId !== null) {
            $filters[] = "breed_id = {$query->breedId}";
        }

        if ($query->city !== null) {
            $filters[] = 'city = '.MeilisearchFilter::quote($query->city);
        }

        if ($query->minPriceAmount !== null) {
            $filters[] = "price_amount >= {$query->minPriceAmount}";
        }

        if ($query->maxPriceAmount !== null) {
            $filters[] = "price_amount <= {$query->maxPriceAmount}";
        }

        $searchParams = [
            'filter' => $filters,
            'limit' => $query->perPage,
            'offset' => ($query->page - 1) * $query->perPage,
        ];

        if ($originLat !== null && $originLng !== null) {
            $searchParams['sort'] = ["_geoPoint({$originLat},{$originLng}):asc"];

            if ($query->radiusKm !== null) {
                $filters[] = "_geoRadius({$originLat}, {$originLng}, ".($query->radiusKm * 1000).')';
                $searchParams['filter'] = $filters;
            }
        }

        $result = $this->index()->search('', $searchParams);

        $items = array_values(array_map(
            static function (array $hit): ListingSearchResult {
                $distanceMeters = $hit['_geoDistance'] ?? null;

                return new ListingSearchResult(
                    id: (string) $hit['id'],
                    petName: (string) $hit['pet_name'],
                    speciesName: $hit['species_name'] ?? null,
                    breedName: $hit['breed_name'] ?? null,
                    city: $hit['city'] ?? null,
                    distanceKm: is_numeric($distanceMeters) ? round(((float) $distanceMeters) / 1000, 1) : null,
                    priceAmount: (int) $hit['price_amount'],
                    currency: (string) $hit['currency'],
                    photoUrl: $hit['photo_url'] ?? null,
                );
            },
            $result->getHits(),
        ));

        return new SearchResultPage(
            items: $items,
            total: $result->getEstimatedTotalHits() ?? count($items),
            page: $query->page,
            perPage: $query->perPage,
        );
    }

    private function index(): Indexes
    {
        return $this->client->index((string) config('meilisearch.listings_index'));
    }
}
