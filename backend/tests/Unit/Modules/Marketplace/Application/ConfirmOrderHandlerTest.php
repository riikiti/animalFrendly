<?php

declare(strict_types=1);

use App\Modules\Marketplace\Application\Commands\ConfirmOrder\ConfirmOrderCommand;
use App\Modules\Marketplace\Application\Commands\ConfirmOrder\ConfirmOrderHandler;
use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Marketplace\Domain\Exceptions\NotOrderPartyException;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function makePaidTestOrder(Id $buyerId, Id $sellerId): Order
{
    $order = Order::create(Id::generate(), Id::generate(), $buyerId, $sellerId, Money::fromMinorUnits(100_000));
    $order->markPaid(Money::fromMinorUnits(5_000), 7);

    return $order;
}

it('does not complete or dispatch an event when only the buyer confirms', function (): void {
    $buyerId = Id::generate();
    $sellerId = Id::generate();
    $order = makePaidTestOrder($buyerId, $sellerId);

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $orders->shouldReceive('findById')->once()->andReturn($order);
    $orders->shouldReceive('save')->once();

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldNotReceive('dispatch');

    $handler = new ConfirmOrderHandler($orders, $events);
    $result = $handler->handle(new ConfirmOrderCommand($order->id()->toString(), $buyerId->toString()));

    expect($result->status())->toBe(OrderStatus::PaidEscrow);
});

it('completes and dispatches OrderCompleted once both sides confirm', function (): void {
    $buyerId = Id::generate();
    $sellerId = Id::generate();
    $order = makePaidTestOrder($buyerId, $sellerId);
    $order->confirmByBuyer();

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $orders->shouldReceive('findById')->once()->andReturn($order);
    $orders->shouldReceive('save')->once();

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once()->with(Mockery::on(
        fn (OrderCompleted $event) => $event->payoutAmount->minorUnits === 95_000,
    ));

    $handler = new ConfirmOrderHandler($orders, $events);
    $result = $handler->handle(new ConfirmOrderCommand($order->id()->toString(), $sellerId->toString()));

    expect($result->status())->toBe(OrderStatus::Completed);
});

it('rejects confirmation from someone who is neither buyer nor seller', function (): void {
    $order = makePaidTestOrder(Id::generate(), Id::generate());

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $orders->shouldReceive('findById')->once()->andReturn($order);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new ConfirmOrderHandler($orders, $events);
    $handler->handle(new ConfirmOrderCommand($order->id()->toString(), Id::generate()->toString()));
})->throws(NotOrderPartyException::class);
