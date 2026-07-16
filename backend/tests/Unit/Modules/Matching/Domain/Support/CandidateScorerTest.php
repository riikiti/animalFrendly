<?php

declare(strict_types=1);

use App\Modules\Matching\Domain\Support\CandidateScorer;

function scoreCandidate(
    ?int $swiperBreedId = null,
    array $swiperTags = [],
    ?DateTimeImmutable $swiperBirthdate = null,
    ?float $swiperLat = null,
    ?float $swiperLng = null,
    ?int $candidateBreedId = null,
    array $candidateTags = [],
    ?DateTimeImmutable $candidateBirthdate = null,
    ?float $candidateLat = null,
    ?float $candidateLng = null,
): float {
    return CandidateScorer::score(
        swiperBreedId: $swiperBreedId,
        swiperTags: $swiperTags,
        swiperBirthdate: $swiperBirthdate,
        swiperLat: $swiperLat,
        swiperLng: $swiperLng,
        candidateBreedId: $candidateBreedId,
        candidateTags: $candidateTags,
        candidateBirthdate: $candidateBirthdate,
        candidateLat: $candidateLat,
        candidateLng: $candidateLng,
    );
}

it('scores a matching breed higher than a non-matching one', function (): void {
    $matching = scoreCandidate(swiperBreedId: 1, candidateBreedId: 1);
    $nonMatching = scoreCandidate(swiperBreedId: 1, candidateBreedId: 2);
    $unknown = scoreCandidate(swiperBreedId: 1, candidateBreedId: null);

    expect($matching)->toBeGreaterThan($nonMatching)
        ->and($nonMatching)->toBe($unknown);
});

it('scores full tag overlap higher than partial or no overlap', function (): void {
    $full = scoreCandidate(swiperTags: ['walks', 'friendship'], candidateTags: ['walks', 'friendship']);
    $partial = scoreCandidate(swiperTags: ['walks', 'friendship'], candidateTags: ['walks']);
    $none = scoreCandidate(swiperTags: ['walks'], candidateTags: ['mating']);
    $missing = scoreCandidate(swiperTags: ['walks'], candidateTags: []);

    expect($full)->toBeGreaterThan($partial)
        ->and($partial)->toBeGreaterThan($none)
        ->and($none)->toBe($missing);
});

it('scores similar ages higher than distant ages, and treats missing birthdates as neutral', function (): void {
    $sameAge = scoreCandidate(
        swiperBirthdate: new DateTimeImmutable('-3 years'),
        candidateBirthdate: new DateTimeImmutable('-3 years'),
    );
    $farApart = scoreCandidate(
        swiperBirthdate: new DateTimeImmutable('-1 years'),
        candidateBirthdate: new DateTimeImmutable('-10 years'),
    );
    $missing = scoreCandidate(swiperBirthdate: new DateTimeImmutable('-3 years'), candidateBirthdate: null);

    expect($sameAge)->toBeGreaterThan($farApart);
    // Отсутствие даты рождения — нейтральный сигнал (0.5 по весу), не наказывает и не
    // помогает сильнее, чем умеренная разница в возрасте.
    expect($missing)->toBeGreaterThan($farApart);
});

it('scores a nearby candidate higher than a distant one, and treats missing coordinates as neutral', function (): void {
    // ~1 км друг от друга в Москве.
    $near = scoreCandidate(swiperLat: 55.751, swiperLng: 37.618, candidateLat: 55.760, candidateLng: 37.618);
    // Москва — Санкт-Петербург, ~630 км.
    $far = scoreCandidate(swiperLat: 55.751, swiperLng: 37.618, candidateLat: 59.939, candidateLng: 30.315);
    $missing = scoreCandidate(swiperLat: 55.751, swiperLng: 37.618, candidateLat: null, candidateLng: null);

    expect($near)->toBeGreaterThan($far)
        ->and($missing)->toBeGreaterThan($far);
});

it('returns the maximum score when every signal matches perfectly', function (): void {
    $birthdate = new DateTimeImmutable('-2 years');

    $score = scoreCandidate(
        swiperBreedId: 1,
        swiperTags: ['walks'],
        swiperBirthdate: $birthdate,
        swiperLat: 55.751,
        swiperLng: 37.618,
        candidateBreedId: 1,
        candidateTags: ['walks'],
        candidateBirthdate: $birthdate,
        candidateLat: 55.751,
        candidateLng: 37.618,
    );

    expect($score)->toEqualWithDelta(1.0, 0.001);
});
