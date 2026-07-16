<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Support;

use App\Shared\Domain\Support\Haversine;
use DateTimeImmutable;

/**
 * Взвешенный скор совместимости кандидата со свайпером — чистая функция без I/O и без
 * зависимости на доменные сущности других модулей (принимает примитивы, которые уже
 * извлёк вызывающий Application-слой) — тот же дух, что Haversine. Буст (платная фича) в
 * скор не входит — это отдельный доминирующий признак сортировки в ListCandidatesHandler,
 * здесь только четыре сигнала совместимости.
 */
final class CandidateScorer
{
    private const float BREED_WEIGHT = 0.25;

    private const float TAGS_WEIGHT = 0.30;

    private const float AGE_WEIGHT = 0.15;

    private const float DISTANCE_WEIGHT = 0.30;

    /**
     * @param  list<string>  $swiperTags
     * @param  list<string>  $candidateTags
     */
    public static function score(
        ?int $swiperBreedId,
        array $swiperTags,
        ?DateTimeImmutable $swiperBirthdate,
        ?float $swiperLat,
        ?float $swiperLng,
        ?int $candidateBreedId,
        array $candidateTags,
        ?DateTimeImmutable $candidateBirthdate,
        ?float $candidateLat,
        ?float $candidateLng,
    ): float {
        return self::BREED_WEIGHT * self::breedScore($swiperBreedId, $candidateBreedId)
            + self::TAGS_WEIGHT * self::tagScore($swiperTags, $candidateTags)
            + self::AGE_WEIGHT * self::ageScore($swiperBirthdate, $candidateBirthdate)
            + self::DISTANCE_WEIGHT * self::distanceScore($swiperLat, $swiperLng, $candidateLat, $candidateLng);
    }

    private static function breedScore(?int $swiperBreedId, ?int $candidateBreedId): float
    {
        if ($swiperBreedId === null || $candidateBreedId === null) {
            return 0.0;
        }

        return $swiperBreedId === $candidateBreedId ? 1.0 : 0.0;
    }

    /**
     * @param  list<string>  $swiperTags
     * @param  list<string>  $candidateTags
     */
    private static function tagScore(array $swiperTags, array $candidateTags): float
    {
        if ($swiperTags === [] || $candidateTags === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($swiperTags, $candidateTags));
        $union = count(array_unique([...$swiperTags, ...$candidateTags]));

        return $intersection / $union;
    }

    private static function ageScore(?DateTimeImmutable $swiperBirthdate, ?DateTimeImmutable $candidateBirthdate): float
    {
        if ($swiperBirthdate === null || $candidateBirthdate === null) {
            return 0.5;
        }

        $deltaYears = abs(self::ageInYears($swiperBirthdate) - self::ageInYears($candidateBirthdate));

        return 1 / (1 + $deltaYears);
    }

    private static function ageInYears(DateTimeImmutable $birthdate): float
    {
        $days = (new DateTimeImmutable)->diff($birthdate)->days;

        return $days / 365.25;
    }

    private static function distanceScore(
        ?float $swiperLat,
        ?float $swiperLng,
        ?float $candidateLat,
        ?float $candidateLng,
    ): float {
        if ($swiperLat === null || $swiperLng === null || $candidateLat === null || $candidateLng === null) {
            return 0.5;
        }

        $km = Haversine::kilometers($swiperLat, $swiperLng, $candidateLat, $candidateLng);

        return 1 / (1 + $km / 10);
    }
}
