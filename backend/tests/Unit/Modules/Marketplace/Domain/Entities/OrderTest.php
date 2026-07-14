<?php

declare(strict_types=1);

use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Marketplace\Domain\Exceptions\OrderAlreadyConfirmedException;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function makeTestOrder(): Order
{
    return Order::create(Id::generate(), Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(1_000_00));
}

it('is created as pending_payment', function (): void {
    expect(makeTestOrder()->status())->toBe(OrderStatus::PendingPayment);
});

it('moves to paid_escrow with commission, payout and escrow hold computed', function (): void {
    $order = makeTestOrder();

    $order->markPaid(Money::fromMinorUnits(5_000), 7);

    expect($order->status())->toBe(OrderStatus::PaidEscrow)
        ->and($order->commissionAmount()->minorUnits)->toBe(5_000)
        ->and($order->payoutAmount()->minorUnits)->toBe(95_000)
        ->and($order->escrowHoldUntil())->not->toBeNull()
        ->and($order->escrowHoldUntil() > new DateTimeImmutable)->toBeTrue();
});

it('completes only once both buyer and seller confirm', function (): void {
    $order = makeTestOrder();
    $order->markPaid(Money::fromMinorUnits(5_000), 7);

    $order->confirmByBuyer();
    expect($order->status())->toBe(OrderStatus::PaidEscrow);

    $order->confirmBySeller();
    expect($order->status())->toBe(OrderStatus::Completed);
});

it('rejects confirming twice by the same party', function (): void {
    $order = makeTestOrder();
    $order->markPaid(Money::fromMinorUnits(5_000), 7);
    $order->confirmByBuyer();

    $order->confirmByBuyer();
})->throws(OrderAlreadyConfirmedException::class);

it('cannot be confirmed before payment', function (): void {
    makeTestOrder()->confirmByBuyer();
})->throws(InvalidOrderStatusTransitionException::class);

it('can be disputed only while paid_escrow, then resolved seller_wins -> completed', function (): void {
    $order = makeTestOrder();
    $order->markPaid(Money::fromMinorUnits(5_000), 7);

    $order->openDispute();
    expect($order->status())->toBe(OrderStatus::Disputed);

    $order->completeFromDispute();
    expect($order->status())->toBe(OrderStatus::Completed);
});

it('resolves buyer_wins -> refunded', function (): void {
    $order = makeTestOrder();
    $order->markPaid(Money::fromMinorUnits(5_000), 7);
    $order->openDispute();

    $order->refundFromDispute();

    expect($order->status())->toBe(OrderStatus::Refunded);
});

it('cannot be disputed twice', function (): void {
    $order = makeTestOrder();
    $order->markPaid(Money::fromMinorUnits(5_000), 7);
    $order->openDispute();

    $order->openDispute();
})->throws(InvalidOrderStatusTransitionException::class);

it('auto-confirms from paid_escrow to completed', function (): void {
    $order = makeTestOrder();
    $order->markPaid(Money::fromMinorUnits(5_000), 7);

    $order->autoConfirm();

    expect($order->status())->toBe(OrderStatus::Completed);
});

it('can be cancelled only while pending_payment', function (): void {
    $order = makeTestOrder();
    $order->cancel();

    expect($order->status())->toBe(OrderStatus::Cancelled);
});

it('cannot be cancelled once paid', function (): void {
    $order = makeTestOrder();
    $order->markPaid(Money::fromMinorUnits(5_000), 7);

    $order->cancel();
})->throws(InvalidOrderStatusTransitionException::class);
