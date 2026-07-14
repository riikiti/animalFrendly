<?php

declare(strict_types=1);

use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Enums\ListingStatus;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotAvailableException;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function makeTestListing(): Listing
{
    return Listing::create(Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(500000));
}

it('is created as draft', function (): void {
    expect(makeTestListing()->status())->toBe(ListingStatus::Draft);
});

it('goes draft -> published -> reserved -> sold', function (): void {
    $listing = makeTestListing();

    $listing->publish();
    expect($listing->status())->toBe(ListingStatus::Published);

    $listing->reserve();
    expect($listing->status())->toBe(ListingStatus::Reserved);

    $listing->markSold();
    expect($listing->status())->toBe(ListingStatus::Sold);
});

it('can go back to published from reserved when payment fails', function (): void {
    $listing = makeTestListing();
    $listing->publish();
    $listing->reserve();

    $listing->backToPublished();

    expect($listing->status())->toBe(ListingStatus::Published);
});

it('cannot be reserved twice', function (): void {
    $listing = makeTestListing();
    $listing->publish();
    $listing->reserve();

    $listing->reserve();
})->throws(ListingNotAvailableException::class);

it('cannot be published twice', function (): void {
    $listing = makeTestListing();
    $listing->publish();
    $listing->publish();
})->throws(ListingNotAvailableException::class);

it('can be archived from draft or published, not from reserved', function (): void {
    $listing = makeTestListing();
    $listing->archive();
    expect($listing->status())->toBe(ListingStatus::Archived);
});

it('cannot be archived once reserved', function (): void {
    $listing = makeTestListing();
    $listing->publish();
    $listing->reserve();

    $listing->archive();
})->throws(ListingNotAvailableException::class);
