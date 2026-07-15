<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\CancelOrder;

use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Events\ListingStatusChanged;
use App\Modules\Marketplace\Domain\Exceptions\NotOrderPartyException;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class CancelOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ListingRepositoryInterface $listings,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(CancelOrderCommand $command): Order
    {
        $order = $this->orders->findById(Id::fromString($command->orderId));

        if ($order === null) {
            throw OrderNotFoundException::forId($command->orderId);
        }

        $actingUserId = Id::fromString($command->actingUserId);

        if (! $order->isBuyer($actingUserId)) {
            throw NotOrderPartyException::create();
        }

        DB::transaction(function () use ($order, $actingUserId): void {
            $order->cancel();
            $this->orders->save($order, $actingUserId, 'buyer_cancelled');

            $listing = $this->listings->findById($order->listingId());

            if ($listing !== null) {
                $listing->backToPublished();
                $this->listings->save($listing);
                $this->events->dispatch(new ListingStatusChanged($listing->id(), $listing->status(), new DateTimeImmutable));
            }
        });

        return $order;
    }
}
