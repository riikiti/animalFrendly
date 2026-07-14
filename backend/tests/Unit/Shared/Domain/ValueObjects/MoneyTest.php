<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObjects\Money;

it('adds money of the same currency', function (): void {
    $sum = Money::fromMinorUnits(1000)->add(Money::fromMinorUnits(500));

    expect($sum->minorUnits)->toBe(1500)
        ->and($sum->currency)->toBe('RUB');
});

it('subtracts money of the same currency', function (): void {
    $diff = Money::fromMinorUnits(1000)->subtract(Money::fromMinorUnits(300));

    expect($diff->minorUnits)->toBe(700);
});

it('rejects mixing currencies', function (): void {
    Money::fromMinorUnits(1000, 'RUB')->add(Money::fromMinorUnits(500, 'USD'));
})->throws(InvalidArgumentException::class);

it('rejects a negative amount', function (): void {
    Money::fromMinorUnits(1000)->subtract(Money::fromMinorUnits(1500));
})->throws(InvalidArgumentException::class);

it('computes a marketplace commission in basis points', function (): void {
    // Комиссия 5% (500 б.п.) со сделки на 65 000.00 ₽, см. docs/plan/08-flow-marketplace-escrow.md
    $dealAmount = Money::fromMinorUnits(6_500_000);

    $commission = $dealAmount->percentage(500);
    $payout = $dealAmount->subtract($commission);

    expect($commission->minorUnits)->toBe(325_000)
        ->and($payout->minorUnits)->toBe(6_175_000);
});

it('compares equality by amount and currency', function (): void {
    expect(Money::fromMinorUnits(100, 'RUB')->equals(Money::fromMinorUnits(100, 'RUB')))->toBeTrue()
        ->and(Money::fromMinorUnits(100, 'RUB')->equals(Money::fromMinorUnits(100, 'USD')))->toBeFalse();
});
