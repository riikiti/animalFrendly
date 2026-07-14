<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\PurchaseListing;

use App\Modules\Marketplace\Application\Contracts\PaymentGatewayInterface;
use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Exceptions\CannotPurchaseOwnListingException;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotAvailableException;
use App\Modules\Marketplace\Domain\Exceptions\ListingNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\DB;

final class PurchaseListingHandler
{
    public function __construct(
        private readonly ListingRepositoryInterface $listings,
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function handle(PurchaseListingCommand $command): PurchaseListingResult
    {
        $listing = $this->listings->findById(Id::fromString($command->listingId));

        if ($listing === null) {
            throw ListingNotFoundException::forId($command->listingId);
        }

        if (! $listing->isPublished()) {
            throw ListingNotAvailableException::create();
        }

        $buyerId = Id::fromString($command->buyerId);

        if ($listing->sellerId()->equals($buyerId)) {
            throw CannotPurchaseOwnListingException::create();
        }

        return DB::transaction(function () use ($listing, $buyerId): PurchaseListingResult {
            $order = Order::create(
                $this->orders->nextIdentity(),
                $listing->id(),
                $buyerId,
                $listing->sellerId(),
                $listing->price(),
            );

            $listing->reserve();
            $this->listings->save($listing);
            $this->orders->save($order, $buyerId, 'order_created');

            $returnUrl = rtrim((string) config('yookassa.frontend_url'), '/')."/orders/{$order->id()->toString()}";
            $result = $this->paymentGateway->initiate($order->id(), $order->amount(), $returnUrl);

            return new PurchaseListingResult($order, $result->confirmationUrl);
        });
    }
}
