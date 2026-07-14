<?php

declare(strict_types=1);

use App\Modules\Matching\Domain\Entities\PetMatch;
use App\Shared\Domain\ValueObjects\Id;

it('orders pet ids consistently regardless of swipe direction', function (): void {
    $petOne = Id::generate();
    $petTwo = Id::generate();

    $matchOneWay = PetMatch::create(Id::generate(), $petOne, $petTwo);
    $matchOtherWay = PetMatch::create(Id::generate(), $petTwo, $petOne);

    expect($matchOneWay->petAId()->toString())->toBe($matchOtherWay->petAId()->toString())
        ->and($matchOneWay->petBId()->toString())->toBe($matchOtherWay->petBId()->toString())
        ->and($matchOneWay->petAId()->toString())->toBeLessThan($matchOneWay->petBId()->toString());
});

it('has no unmatched_at right after creation', function (): void {
    $match = PetMatch::create(Id::generate(), Id::generate(), Id::generate());

    expect($match->unmatchedAt())->toBeNull();
});
