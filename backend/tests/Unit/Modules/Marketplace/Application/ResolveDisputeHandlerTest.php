<?php

declare(strict_types=1);

use App\Modules\Marketplace\Application\Commands\ResolveDispute\ResolveDisputeCommand;
use App\Modules\Marketplace\Application\Commands\ResolveDispute\ResolveDisputeHandler;
use App\Modules\Marketplace\Domain\Entities\Dispute;
use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Marketplace\Domain\Events\OrderRefunded;
use App\Modules\Marketplace\Domain\Exceptions\DisputeNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\DisputeRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function makeDisputedTestOrder(): Order
{
    $order = Order::create(Id::generate(), Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(100_000));
    $order->markPaid(Money::fromMinorUnits(5_000), 7);
    $order->openDispute();

    return $order;
}

it('completes the order and dispatches OrderCompleted when seller_wins', function (): void {
    $order = makeDisputedTestOrder();
    $dispute = Dispute::open(Id::generate(), $order->id(), Id::generate(), 'Причина');

    $disputes = Mockery::mock(DisputeRepositoryInterface::class);
    $disputes->shouldReceive('findById')->once()->andReturn($dispute);
    $disputes->shouldReceive('save')->once();

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $orders->shouldReceive('findById')->once()->andReturn($order);
    $orders->shouldReceive('save')->once();

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once()->with(Mockery::type(OrderCompleted::class));

    $handler = new ResolveDisputeHandler($disputes, $orders, $events);
    $handler->handle(new ResolveDisputeCommand($dispute->id()->toString(), Id::generate()->toString(), 'seller_wins'));

    expect($order->status())->toBe(OrderStatus::Completed);
});

it('refunds the order and dispatches OrderRefunded when buyer_wins', function (): void {
    $order = makeDisputedTestOrder();
    $dispute = Dispute::open(Id::generate(), $order->id(), Id::generate(), 'Причина');

    $disputes = Mockery::mock(DisputeRepositoryInterface::class);
    $disputes->shouldReceive('findById')->once()->andReturn($dispute);
    $disputes->shouldReceive('save')->once();

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $orders->shouldReceive('findById')->once()->andReturn($order);
    $orders->shouldReceive('save')->once();

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once()->with(Mockery::type(OrderRefunded::class));

    $handler = new ResolveDisputeHandler($disputes, $orders, $events);
    $handler->handle(new ResolveDisputeCommand($dispute->id()->toString(), Id::generate()->toString(), 'buyer_wins'));

    expect($order->status())->toBe(OrderStatus::Refunded);
});

it('rejects resolving a dispute that does not exist', function (): void {
    $disputes = Mockery::mock(DisputeRepositoryInterface::class);
    $disputes->shouldReceive('findById')->once()->andReturn(null);

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new ResolveDisputeHandler($disputes, $orders, $events);
    $handler->handle(new ResolveDisputeCommand(Id::generate()->toString(), Id::generate()->toString(), 'seller_wins'));
})->throws(DisputeNotFoundException::class);
