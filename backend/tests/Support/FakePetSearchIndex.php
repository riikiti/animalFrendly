<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use App\Modules\Search\Application\DTO\PetSearchResult;
use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Modules\Search\Application\Queries\SearchPets\SearchPetsQuery;

/**
 * In-memory замена Meilisearch для тестов — наивная фильтрация/сортировка массивом, вместо
 * реального индекса. PetSearchIndexInterface и ListingSearchIndexInterface объявляют
 * одноимённый search() с разными типами параметра, поэтому один класс не может реализовать
 * оба (см. FakeListingSearchIndex для второй половины) — держим общую логику в FakeSearchStore.
 */
final class FakePetSearchIndex implements PetSearchIndexInterface
{
    public function __construct(private readonly FakeSearchStore $store = new FakeSearchStore) {}

    public function configureIndex(): void {}

    public function putDocument(array $document): void
    {
        $this->store->put('pets', (string) $document['id'], $document);
    }

    public function deleteDocument(string $id): void
    {
        $this->store->delete('pets', $id);
    }

    public function deleteAll(): void
    {
        $this->store->deleteAll('pets');
    }

    public function search(SearchPetsQuery $query, ?float $originLat, ?float $originLng): SearchResultPage
    {
        $documents = array_filter($this->store->all('pets'), function (array $doc) use ($query): bool {
            if ($query->speciesId !== null && $doc['species_id'] !== $query->speciesId) {
                return false;
            }

            if ($query->breedId !== null && $doc['breed_id'] !== $query->breedId) {
                return false;
            }

            if ($query->sex !== null && $doc['sex'] !== $query->sex) {
                return false;
            }

            if ($query->purpose !== null && $doc['purpose'] !== $query->purpose) {
                return false;
            }

            if ($query->city !== null && $doc['city'] !== $query->city) {
                return false;
            }

            if ($query->isVaccinated !== null && $doc['is_vaccinated'] !== $query->isVaccinated) {
                return false;
            }

            return true;
        });

        $items = array_values(array_map(
            static fn (array $doc): PetSearchResult => new PetSearchResult(
                id: $doc['id'],
                name: $doc['name'],
                speciesName: $doc['species_name'],
                breedName: $doc['breed_name'],
                sex: $doc['sex'],
                purpose: $doc['purpose'],
                city: $doc['city'],
                distanceKm: FakeSearchStore::distanceKm($doc, $originLat, $originLng),
                isVaccinated: $doc['is_vaccinated'],
                isBoosted: $doc['is_boosted'],
                photoUrl: $doc['photo_url'],
            ),
            $documents,
        ));

        return new SearchResultPage($items, count($items), $query->page, $query->perPage);
    }
}
