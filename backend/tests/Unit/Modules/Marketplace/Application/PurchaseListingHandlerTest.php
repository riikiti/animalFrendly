<?php

declare(strict_types=1);

use App\Modules\Marketplace\Application\Commands\PurchaseListing\PurchaseListingCommand;
use App\Modules\Marketplace\Application\Commands\PurchaseListing\PurchaseListingHandler;
use App\Modules\Marketplace\Application\Contracts\PaymentGatewayInterface;
use App\Modules\Marketplace\Application\Contracts\PaymentInitiationResult;
use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Marketplace\Domain\Enums\ListingStatus;
use App\Modules\Marketplace\Domain\Exceptions\CannotPurchaseOwnListingException;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotAvailableException;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

it('reserves the listing, creates a pending order and returns the gateway confirmation url', function (): void {
    $sellerId = Id::generate();
    $buyerId = Id::generate();
    $listing = Listing::create(Id::generate(), $sellerId, Id::generate(), Money::fromMinorUnits(500_00));
    $listing->publish();

    $listings = Mockery::mock(ListingRepositoryInterface::class);
    $listings->shouldReceive('findById')->once()->andReturn($listing);
    $listings->shouldReceive('save')->once()->with(Mockery::on(fn (Listing $l) => $l->status() === ListingStatus::Reserved));

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $orders->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $orders->shouldReceive('save')->once();

    $gateway = Mockery::mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('initiate')->once()->andReturn(new PaymentInitiationResult('https://yookassa.ru/pay/123', 'yk-123'));

    $handler = new PurchaseListingHandler($listings, $orders, $gateway);
    $result = $handler->handle(new PurchaseListingCommand($listing->id()->toString(), $buyerId->toString()));

    expect($result->confirmationUrl)->toBe('https://yookassa.ru/pay/123')
        ->and($result->order->buyerId()->equals($buyerId))->toBeTrue()
        ->and($result->order->sellerId()->equals($sellerId))->toBeTrue();
});

it('rejects purchasing your own listing', function (): void {
    $sellerId = Id::generate();
    $listing = Listing::create(Id::generate(), $sellerId, Id::generate(), Money::fromMinorUnits(500_00));
    $listing->publish();

    $listings = Mockery::mock(ListingRepositoryInterface::class);
    $listings->shouldReceive('findById')->once()->andReturn($listing);

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $handler = new PurchaseListingHandler($listings, $orders, $gateway);
    $handler->handle(new PurchaseListingCommand($listing->id()->toString(), $sellerId->toString()));
})->throws(CannotPurchaseOwnListingException::class);

it('rejects purchasing a listing that is not published', function (): void {
    $listing = Listing::create(Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(500_00));

    $listings = Mockery::mock(ListingRepositoryInterface::class);
    $listings->shouldReceive('findById')->once()->andReturn($listing);

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $handler = new PurchaseListingHandler($listings, $orders, $gateway);
    $handler->handle(new PurchaseListingCommand($listing->id()->toString(), Id::generate()->toString()));
})->throws(ListingNotAvailableException::class);

it('rejects purchasing a listing that does not exist', function (): void {
    $listings = Mockery::mock(ListingRepositoryInterface::class);
    $listings->shouldReceive('findById')->once()->andReturn(null);

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $handler = new PurchaseListingHandler($listings, $orders, $gateway);
    $handler->handle(new PurchaseListingCommand(Id::generate()->toString(), Id::generate()->toString()));
})->throws(ListingNotFoundException::class);
