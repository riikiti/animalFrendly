<?php

declare(strict_types=1);

namespace App\Shared\Domain\Support;

/**
 * Расстояние по прямой между двумя точками на сфере — переиспользуемая замена ручному
 * haversine, который до этой фазы существовал только в tests/Support/FakeSearchStore
 * (тестовый дубль для параллели с geo-сортировкой Meilisearch). Здесь — для мест, где
 * Meilisearch не участвует (лента животных приютов, список приютов), без внешних
 * зависимостей.
 */
final class Haversine
{
    private const float EARTH_RADIUS_KM = 6371.0;

    public static function kilometers(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return round(self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
