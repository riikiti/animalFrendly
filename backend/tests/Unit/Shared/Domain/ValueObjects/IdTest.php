<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObjects\Id;

it('generates a valid, unique ULID each time', function (): void {
    $a = Id::generate();
    $b = Id::generate();

    expect($a->toString())->not->toBe($b->toString())
        ->and(strlen($a->toString()))->toBe(26);
});

it('round-trips through fromString', function (): void {
    $id = Id::generate();

    expect(Id::fromString($id->toString())->equals($id))->toBeTrue();
});

it('rejects an invalid ULID', function (): void {
    Id::fromString('not-a-ulid');
})->throws(InvalidArgumentException::class);
