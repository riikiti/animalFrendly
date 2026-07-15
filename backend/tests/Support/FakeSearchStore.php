<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Общее in-memory хранилище документов для FakePetSearchIndex/FakeListingSearchIndex —
 * наивный аналог Meilisearch (без реальной геометрии, простая haversine для дистанции).
 */
final class FakeSearchStore
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private array $documents = [];

    /**
     * @param  array<string, mixed>  $document
     */
    public function put(string $index, string $id, array $document): void
    {
        $this->documents[$index][$id] = $document;
    }

    public function delete(string $index, string $id): void
    {
        unset($this->documents[$index][$id]);
    }

    public function deleteAll(string $index): void
    {
        $this->documents[$index] = [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(string $index): array
    {
        return array_values($this->documents[$index] ?? []);
    }

    /**
     * @param  array<string, mixed>  $document
     */
    public static function distanceKm(array $document, ?float $originLat, ?float $originLng): ?float
    {
        if ($originLat === null || $originLng === null || ! isset($document['_geo'])) {
            return null;
        }

        $geo = $document['_geo'];
        $earthRadiusKm = 6371.0;

        $latDelta = deg2rad($geo['lat'] - $originLat);
        $lngDelta = deg2rad($geo['lng'] - $originLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($originLat)) * cos(deg2rad($geo['lat'])) * sin($lngDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
