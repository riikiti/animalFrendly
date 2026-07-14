<?php

declare(strict_types=1);

use App\Modules\Payment\Domain\Entities\Payout;
use App\Modules\Payment\Domain\Enums\PayoutStatus;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

it('is created as pending', function (): void {
    $payout = Payout::create(Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(95_000));

    expect($payout->status())->toBe(PayoutStatus::Pending)
        ->and($payout->processedAt())->toBeNull();
});

it('moves through processing to paid with a yookassa id and processed timestamp', function (): void {
    $payout = Payout::create(Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(95_000));

    $payout->markProcessing();
    expect($payout->status())->toBe(PayoutStatus::Processing);

    $payout->markPaid('yk-payout-1');

    expect($payout->status())->toBe(PayoutStatus::Paid)
        ->and($payout->yookassaPayoutId())->toBe('yk-payout-1')
        ->and($payout->processedAt())->not->toBeNull();
});

it('can be marked failed without blocking the order', function (): void {
    $payout = Payout::create(Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(95_000));

    $payout->markFailed();

    expect($payout->status())->toBe(PayoutStatus::Failed);
});
