<?php

declare(strict_types=1);

use App\Modules\Subscription\Application\Services\SubscriptionFeatureGate;
use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Modules\Subscription\Domain\Entities\SubscriptionPlan;
use App\Modules\Subscription\Domain\Enums\BillingPeriod;
use App\Modules\Subscription\Domain\Enums\FeatureKey;
use App\Modules\Subscription\Domain\Repositories\FeatureUsageRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function makeFeatureGatePlan(int $id, array $features): SubscriptionPlan
{
    return SubscriptionPlan::reconstitute(
        id: $id,
        slug: $id === 1 ? 'free' : 'plus',
        nameRu: $id === 1 ? 'Free' : 'Plus',
        price: Money::zero(),
        period: BillingPeriod::Month,
        features: $features,
        isActive: true,
    );
}

it('never blocks an unlimited feature', function (): void {
    $userId = Id::generate();
    $subscription = Subscription::subscribe(Id::generate(), $userId, 2);
    $subscription->activate(new DateTimeImmutable('+1 month'), 'pm-123');

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->andReturn($subscription);

    $plans = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
    $plans->shouldReceive('findById')->once()->with(2)->andReturn(makeFeatureGatePlan(2, ['daily_likes' => null]));

    $usage = Mockery::mock(FeatureUsageRepositoryInterface::class);
    $usage->shouldReceive('tryConsume')->once()->with($userId, FeatureKey::DailyLike, Mockery::any(), null)->andReturn(true);

    $gate = new SubscriptionFeatureGate($subscriptions, $plans, $usage);

    expect($gate->consume($userId, FeatureKey::DailyLike))->toBeTrue();
});

it('blocks once the tariff limit is exhausted', function (): void {
    $userId = Id::generate();

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->andReturn(null);

    $plans = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
    $plans->shouldReceive('findBySlug')->once()->with('free')->andReturn(makeFeatureGatePlan(1, ['daily_likes' => 20]));

    $usage = Mockery::mock(FeatureUsageRepositoryInterface::class);
    $usage->shouldReceive('tryConsume')->once()->with($userId, FeatureKey::DailyLike, Mockery::any(), 20)->andReturn(false);

    $gate = new SubscriptionFeatureGate($subscriptions, $plans, $usage);

    expect($gate->consume($userId, FeatureKey::DailyLike))->toBeFalse();
});

it('falls back to the free plan when there is no active subscription', function (): void {
    $userId = Id::generate();

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->andReturn(null);

    $plans = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
    $plans->shouldReceive('findBySlug')->once()->with('free')->andReturn(makeFeatureGatePlan(1, ['marketplace_commission_bps' => 500]));

    $usage = Mockery::mock(FeatureUsageRepositoryInterface::class);

    $gate = new SubscriptionFeatureGate($subscriptions, $plans, $usage);

    expect($gate->commissionBasisPointsFor($userId))->toBe(500);
});

it('does not block actions when the free plan is missing entirely', function (): void {
    $userId = Id::generate();

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->andReturn(null);

    $plans = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
    $plans->shouldReceive('findBySlug')->once()->with('free')->andReturn(null);

    $usage = Mockery::mock(FeatureUsageRepositoryInterface::class);
    $usage->shouldNotReceive('tryConsume');

    $gate = new SubscriptionFeatureGate($subscriptions, $plans, $usage);

    expect($gate->consume($userId, FeatureKey::DailyLike))->toBeTrue();
});

it('resolves the commission rate from the seller plan', function (): void {
    $userId = Id::generate();
    $subscription = Subscription::subscribe(Id::generate(), $userId, 3);
    $subscription->activate(new DateTimeImmutable('+1 month'), 'pm-123');

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->andReturn($subscription);

    $plans = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
    $plans->shouldReceive('findById')->once()->with(3)->andReturn(makeFeatureGatePlan(3, ['marketplace_commission_bps' => 400]));

    $usage = Mockery::mock(FeatureUsageRepositoryInterface::class);

    $gate = new SubscriptionFeatureGate($subscriptions, $plans, $usage);

    expect($gate->commissionBasisPointsFor($userId))->toBe(400);
});
