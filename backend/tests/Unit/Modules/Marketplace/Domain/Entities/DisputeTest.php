<?php

declare(strict_types=1);

use App\Modules\Marketplace\Domain\Entities\Dispute;
use App\Modules\Marketplace\Domain\Enums\DisputeResolution;
use App\Modules\Marketplace\Domain\Exceptions\DisputeAlreadyResolvedException;
use App\Shared\Domain\ValueObjects\Id;

it('is open with no resolution', function (): void {
    $dispute = Dispute::open(Id::generate(), Id::generate(), Id::generate(), 'Питомец не соответствует описанию');

    expect($dispute->isResolved())->toBeFalse();
});

it('can be resolved once', function (): void {
    $dispute = Dispute::open(Id::generate(), Id::generate(), Id::generate(), 'Причина');
    $resolver = Id::generate();

    $dispute->resolve(DisputeResolution::BuyerWins, $resolver);

    expect($dispute->isResolved())->toBeTrue()
        ->and($dispute->resolution())->toBe(DisputeResolution::BuyerWins)
        ->and($dispute->resolvedBy()?->equals($resolver))->toBeTrue();
});

it('cannot be resolved twice', function (): void {
    $dispute = Dispute::open(Id::generate(), Id::generate(), Id::generate(), 'Причина');
    $dispute->resolve(DisputeResolution::SellerWins, Id::generate());

    $dispute->resolve(DisputeResolution::BuyerWins, Id::generate());
})->throws(DisputeAlreadyResolvedException::class);
