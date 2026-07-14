<?php

declare(strict_types=1);

use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Application\Services\CreatePayoutService;
use App\Modules\Payment\Domain\Entities\Payout;
use App\Modules\Payment\Domain\Enums\PayoutStatus;
use App\Modules\Payment\Domain\Repositories\PayoutRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

it('is idempotent when a payout already exists for the order', function (): void {
    $orderId = Id::generate();
    $existing = Payout::create(Id::generate(), $orderId, Id::generate(), Money::fromMinorUnits(95_000));

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('findByOrderId')->once()->with($orderId)->andReturn($existing);
    $payouts->shouldNotReceive('save');

    $client = Mockery::mock(YookassaClientInterface::class);
    $client->shouldNotReceive('createPayout');

    (new CreatePayoutService($payouts, $client))->createForOrder($orderId, Id::generate(), Money::fromMinorUnits(95_000));
});

it('creates a pending payout without calling the gateway when payouts are disabled', function (): void {
    config(['yookassa.payouts_enabled' => false]);

    $orderId = Id::generate();

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('findByOrderId')->once()->andReturn(null);
    $payouts->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $payouts->shouldReceive('save')->once()->with(Mockery::on(fn (Payout $p) => $p->status() === PayoutStatus::Pending));

    $client = Mockery::mock(YookassaClientInterface::class);
    $client->shouldNotReceive('createPayout');

    (new CreatePayoutService($payouts, $client))->createForOrder($orderId, Id::generate(), Money::fromMinorUnits(95_000));
});

it('calls the gateway and marks the payout paid when payouts are enabled', function (): void {
    config(['yookassa.payouts_enabled' => true]);

    $orderId = Id::generate();

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('findByOrderId')->once()->andReturn(null);
    $payouts->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $payouts->shouldReceive('save')->times(3);

    $client = Mockery::mock(YookassaClientInterface::class);
    $client->shouldReceive('createPayout')->once()->andReturn(['id' => 'yk-payout-1']);

    (new CreatePayoutService($payouts, $client))->createForOrder($orderId, Id::generate(), Money::fromMinorUnits(95_000));
});

it('marks the payout failed without throwing when the gateway call fails', function (): void {
    config(['yookassa.payouts_enabled' => true]);

    $orderId = Id::generate();
    $savedStatuses = [];

    $payouts = Mockery::mock(PayoutRepositoryInterface::class);
    $payouts->shouldReceive('findByOrderId')->once()->andReturn(null);
    $payouts->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $payouts->shouldReceive('save')->times(3)->with(Mockery::on(function (Payout $p) use (&$savedStatuses) {
        $savedStatuses[] = $p->status();

        return true;
    }));

    $client = Mockery::mock(YookassaClientInterface::class);
    $client->shouldReceive('createPayout')->once()->andThrow(new RuntimeException('ЮKassa недоступна'));

    (new CreatePayoutService($payouts, $client))->createForOrder($orderId, Id::generate(), Money::fromMinorUnits(95_000));

    expect(end($savedStatuses))->toBe(PayoutStatus::Failed);
});
