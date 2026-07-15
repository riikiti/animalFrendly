<?php

declare(strict_types=1);

namespace App\Modules\Search\Infrastructure\Search;

use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use App\Modules\Search\Application\DTO\PetSearchResult;
use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Modules\Search\Application\Queries\SearchPets\SearchPetsQuery;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;

final class MeilisearchPetIndex implements PetSearchIndexInterface
{
    public function __construct(private readonly Client $client) {}

    public function configureIndex(): void
    {
        $index = $this->index();
        $index->updateFilterableAttributes(['species_id', 'breed_id', 'sex', 'purpose', 'city', 'is_vaccinated', '_geo']);
        $index->updateSortableAttributes(['is_boosted', '_geo']);
        $index->updateSearchableAttributes(['name', 'species_name', 'breed_name']);
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

    public function search(SearchPetsQuery $query, ?float $originLat, ?float $originLng): SearchResultPage
    {
        $filters = [];

        if ($query->speciesId !== null) {
            $filters[] = "species_id = {$query->speciesId}";
        }

        if ($query->breedId !== null) {
            $filters[] = "breed_id = {$query->breedId}";
        }

        if ($query->sex !== null) {
            $filters[] = 'sex = '.MeilisearchFilter::quote($query->sex);
        }

        if ($query->purpose !== null) {
            $filters[] = 'purpose = '.MeilisearchFilter::quote($query->purpose);
        }

        if ($query->city !== null) {
            $filters[] = 'city = '.MeilisearchFilter::quote($query->city);
        }

        if ($query->isVaccinated !== null) {
            $filters[] = 'is_vaccinated = '.($query->isVaccinated ? 'true' : 'false');
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
        } else {
            $searchParams['sort'] = ['is_boosted:desc'];
        }

        $result = $this->index()->search('', $searchParams);

        $items = array_values(array_map(
            static function (array $hit): PetSearchResult {
                $distanceMeters = $hit['_geoDistance'] ?? null;

                return new PetSearchResult(
                    id: (string) $hit['id'],
                    name: (string) $hit['name'],
                    speciesName: $hit['species_name'] ?? null,
                    breedName: $hit['breed_name'] ?? null,
                    sex: (string) $hit['sex'],
                    purpose: (string) $hit['purpose'],
                    city: $hit['city'] ?? null,
                    distanceKm: is_numeric($distanceMeters) ? round(((float) $distanceMeters) / 1000, 1) : null,
                    isVaccinated: (bool) $hit['is_vaccinated'],
                    isBoosted: (bool) $hit['is_boosted'],
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
        return $this->client->index((string) config('meilisearch.pets_index'));
    }
}
