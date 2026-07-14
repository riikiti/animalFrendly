<?php

declare(strict_types=1);

use App\Modules\Payment\Domain\Entities\Payment;
use App\Modules\Payment\Domain\Enums\PaymentStatus;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

it('is created as pending with no yookassa id', function (): void {
    $payment = Payment::create(Id::generate(), 'order', Id::generate(), Money::fromMinorUnits(10_000), 'idem-key');

    expect($payment->status())->toBe(PaymentStatus::Pending)
        ->and($payment->yookassaPaymentId())->toBeNull()
        ->and($payment->isTerminal())->toBeFalse();
});

it('attaches the yookassa id after the gateway call', function (): void {
    $payment = Payment::create(Id::generate(), 'order', Id::generate(), Money::fromMinorUnits(10_000), 'idem-key');

    $payment->attachYookassaId('yk-123');

    expect($payment->yookassaPaymentId())->toBe('yk-123');
});

it('marks succeeded/canceled/refunded as terminal', function (): void {
    $succeeded = Payment::create(Id::generate(), 'order', Id::generate(), Money::fromMinorUnits(10_000), 'k1');
    $succeeded->markSucceeded(['id' => 'yk-1']);
    expect($succeeded->status())->toBe(PaymentStatus::Succeeded)->and($succeeded->isTerminal())->toBeTrue();

    $canceled = Payment::create(Id::generate(), 'order', Id::generate(), Money::fromMinorUnits(10_000), 'k2');
    $canceled->markCanceled(['id' => 'yk-2']);
    expect($canceled->status())->toBe(PaymentStatus::Canceled)->and($canceled->isTerminal())->toBeTrue();

    $refunded = Payment::create(Id::generate(), 'order', Id::generate(), Money::fromMinorUnits(10_000), 'k3');
    $refunded->markRefunded(['id' => 'yk-3']);
    expect($refunded->status())->toBe(PaymentStatus::Refunded)->and($refunded->isTerminal())->toBeTrue();
});
