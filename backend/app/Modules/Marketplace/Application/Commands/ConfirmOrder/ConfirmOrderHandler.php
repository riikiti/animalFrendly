<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\ConfirmOrder;

use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Marketplace\Domain\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Marketplace\Domain\Exceptions\NotOrderPartyException;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class ConfirmOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(ConfirmOrderCommand $command): Order
    {
        $order = $this->orders->findById(Id::fromString($command->orderId));

        if ($order === null) {
            throw OrderNotFoundException::forId($command->orderId);
        }

        $actingUserId = Id::fromString($command->actingUserId);
        $reason = null;

        if ($order->isBuyer($actingUserId)) {
            $order->confirmByBuyer();
            $reason = 'buyer_confirmed';
        } elseif ($order->isSeller($actingUserId)) {
            $order->confirmBySeller();
            $reason = 'seller_confirmed';
        } else {
            throw NotOrderPartyException::create();
        }

        $becameCompleted = $order->status() === OrderStatus::Completed;

        $this->orders->save($order, $actingUserId, $becameCompleted ? 'both_confirmed' : $reason);

        if ($becameCompleted) {
            $payoutAmount = $order->payoutAmount();

            if ($payoutAmount === null) {
                throw InvalidOrderStatusTransitionException::create();
            }

            $this->events->dispatch(new OrderCompleted(
                $order->id(),
                $order->sellerId(),
                $payoutAmount,
                new DateTimeImmutable,
            ));
        }

        return $order;
    }
}
