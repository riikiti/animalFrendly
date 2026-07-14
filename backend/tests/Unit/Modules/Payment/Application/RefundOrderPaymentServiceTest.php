<?php

declare(strict_types=1);

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Application\Services\RefundOrderPaymentService;
use App\Modules\Payment\Domain\Entities\Payment;
use App\Modules\Payment\Domain\Enums\PaymentStatus;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

it('calls the gateway refund for a succeeded payment', function (): void {
    $orderId = Id::generate();
    $payment = Payment::create(Id::generate(), 'order', $orderId, Money::fromMinorUnits(100_000), 'k');
    $payment->attachYookassaId('yk-123');
    $payment->markSucceeded(['id' => 'yk-123']);

    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldReceive('findByPayable')->once()->with('order', $orderId)->andReturn($payment);

    $client = Mockery::mock(YookassaClientInterface::class);
    $client->shouldReceive('createRefund')->once()->with('yk-123', Mockery::on(fn (Money $m) => $m->minorUnits === 100_000), "{$orderId->toString()}:refund");

    (new RefundOrderPaymentService($payments, $client))->refundForOrder($orderId);
});

it('does nothing when there is no payment for the order', function (): void {
    $orderId = Id::generate();

    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldReceive('findByPayable')->once()->andReturn(null);

    $client = Mockery::mock(YookassaClientInterface::class);
    $client->shouldNotReceive('createRefund');

    (new RefundOrderPaymentService($payments, $client))->refundForOrder($orderId);
});

it('does nothing when the payment was never succeeded', function (): void {
    $orderId = Id::generate();
    $payment = Payment::create(Id::generate(), 'order', $orderId, Money::fromMinorUnits(100_000), 'k');

    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldReceive('findByPayable')->once()->andReturn($payment);

    $client = Mockery::mock(YookassaClientInterface::class);
    $client->shouldNotReceive('createRefund');

    (new RefundOrderPaymentService($payments, $client))->refundForOrder($orderId);

    expect($payment->status())->toBe(PaymentStatus::Pending);
});
