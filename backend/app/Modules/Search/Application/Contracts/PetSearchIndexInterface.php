<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Contracts;

use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Modules\Search\Application\Queries\SearchPets\SearchPetsQuery;

interface PetSearchIndexInterface
{
    /**
     * Идемпотентно выставляет filterable/sortable/searchable атрибуты индекса.
     */
    public function configureIndex(): void;

    /**
     * @param  array<string, mixed>  $document
     */
    public function putDocument(array $document): void;

    public function deleteDocument(string $id): void;

    public function deleteAll(): void;

    /**
     * $originLat/$originLng — координаты текущего пользователя (для сортировки/фильтра по
     * расстоянию), null если у него нет координат — в этом случае сортировка по расстоянию
     * просто недоступна.
     */
    public function search(SearchPetsQuery $query, ?float $originLat, ?float $originLng): SearchResultPage;
}
