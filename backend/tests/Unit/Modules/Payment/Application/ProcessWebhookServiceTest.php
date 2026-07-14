<?php

declare(strict_types=1);

use App\Modules\Payment\Application\Services\ProcessWebhookService;
use App\Modules\Payment\Domain\Entities\Payment;
use App\Modules\Payment\Domain\Enums\PaymentStatus;
use App\Modules\Payment\Domain\Events\PaymentCanceled;
use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function makeTestPayment(PaymentStatus $status = PaymentStatus::Pending): Payment
{
    $payment = Payment::create(Id::generate(), 'order', Id::generate(), Money::fromMinorUnits(100_000), 'order-id:create');
    $payment->attachYookassaId('yk-123');

    if ($status === PaymentStatus::Succeeded) {
        $payment->markSucceeded(['id' => 'yk-123']);
    } elseif ($status === PaymentStatus::Canceled) {
        $payment->markCanceled(['id' => 'yk-123']);
    }

    return $payment;
}

it('marks the payment succeeded and dispatches PaymentSucceeded', function (): void {
    $payment = makeTestPayment();

    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldReceive('findByYookassaId')->once()->with('yk-123')->andReturn($payment);
    $payments->shouldReceive('save')->once();

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once()->with(Mockery::type(PaymentSucceeded::class));

    (new ProcessWebhookService($payments, $events))->process([
        'event' => 'payment.succeeded',
        'object' => ['id' => 'yk-123', 'status' => 'succeeded'],
    ]);

    expect($payment->status())->toBe(PaymentStatus::Succeeded);
});

it('is idempotent when the same succeeded webhook arrives twice', function (): void {
    $payment = makeTestPayment(PaymentStatus::Succeeded);

    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldReceive('findByYookassaId')->once()->with('yk-123')->andReturn($payment);
    $payments->shouldNotReceive('save');

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldNotReceive('dispatch');

    (new ProcessWebhookService($payments, $events))->process([
        'event' => 'payment.succeeded',
        'object' => ['id' => 'yk-123', 'status' => 'succeeded'],
    ]);
});

it('marks the payment canceled and dispatches PaymentCanceled', function (): void {
    $payment = makeTestPayment();

    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldReceive('findByYookassaId')->once()->andReturn($payment);
    $payments->shouldReceive('save')->once();

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once()->with(Mockery::type(PaymentCanceled::class));

    (new ProcessWebhookService($payments, $events))->process([
        'event' => 'payment.canceled',
        'object' => ['id' => 'yk-123', 'status' => 'canceled'],
    ]);

    expect($payment->status())->toBe(PaymentStatus::Canceled);
});

it('ignores a webhook for an unknown payment', function (): void {
    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldReceive('findByYookassaId')->once()->andReturn(null);
    $payments->shouldNotReceive('save');

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldNotReceive('dispatch');

    (new ProcessWebhookService($payments, $events))->process([
        'event' => 'payment.succeeded',
        'object' => ['id' => 'yk-unknown'],
    ]);
});

it('ignores a malformed payload', function (): void {
    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldNotReceive('findByYookassaId');

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldNotReceive('dispatch');

    (new ProcessWebhookService($payments, $events))->process(['event' => 'payment.succeeded']);
});
