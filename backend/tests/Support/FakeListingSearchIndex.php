<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Modules\Search\Application\DTO\ListingSearchResult;
use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Modules\Search\Application\Queries\SearchListings\SearchListingsQuery;

final class FakeListingSearchIndex implements ListingSearchIndexInterface
{
    public function __construct(private readonly FakeSearchStore $store = new FakeSearchStore) {}

    public function configureIndex(): void {}

    public function putDocument(array $document): void
    {
        $this->store->put('listings', (string) $document['id'], $document);
    }

    public function deleteDocument(string $id): void
    {
        $this->store->delete('listings', $id);
    }

    public function deleteAll(): void
    {
        $this->store->deleteAll('listings');
    }

    public function search(SearchListingsQuery $query, ?float $originLat, ?float $originLng): SearchResultPage
    {
        $documents = array_filter($this->store->all('listings'), function (array $doc) use ($query): bool {
            if ($query->q !== null && ! str_contains(mb_strtolower((string) $doc['pet_name']), mb_strtolower($query->q))) {
                return false;
            }

            if ($query->speciesId !== null && $doc['species_id'] !== $query->speciesId) {
                return false;
            }

            if ($query->breedId !== null && $doc['breed_id'] !== $query->breedId) {
                return false;
            }

            if ($query->city !== null && $doc['city'] !== $query->city) {
                return false;
            }

            if ($query->minPriceAmount !== null && $doc['price_amount'] < $query->minPriceAmount) {
                return false;
            }

            if ($query->maxPriceAmount !== null && $doc['price_amount'] > $query->maxPriceAmount) {
                return false;
            }

            return true;
        });

        $items = array_values(array_map(
            static fn (array $doc): ListingSearchResult => new ListingSearchResult(
                id: $doc['id'],
                petName: $doc['pet_name'],
                speciesName: $doc['species_name'],
                breedName: $doc['breed_name'],
                city: $doc['city'],
                distanceKm: FakeSearchStore::distanceKm($doc, $originLat, $originLng),
                priceAmount: $doc['price_amount'],
                currency: $doc['currency'],
                photoUrl: $doc['photo_url'],
            ),
            $documents,
        ));

        return new SearchResultPage($items, count($items), $query->page, $query->perPage);
    }
}
