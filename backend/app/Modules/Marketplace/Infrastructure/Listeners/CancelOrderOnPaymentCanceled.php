<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Listeners;

use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Events\ListingStatusChanged;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Modules\Payment\Domain\Events\PaymentCanceled;
use App\Shared\Application\DomainEventDispatcherInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class CancelOrderOnPaymentCanceled
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ListingRepositoryInterface $listings,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(PaymentCanceled $event): void
    {
        if ($event->payableType !== 'order') {
            return;
        }

        $order = $this->orders->findById($event->payableId);

        if ($order === null || $order->status() !== OrderStatus::PendingPayment) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->cancel();
            $this->orders->save($order, null, 'payment_canceled');

            $listing = $this->listings->findById($order->listingId());

            if ($listing !== null) {
                $listing->backToPublished();
                $this->listings->save($listing);
                $this->events->dispatch(new ListingStatusChanged($listing->id(), $listing->status(), new DateTimeImmutable));
            }
        });
    }
}
