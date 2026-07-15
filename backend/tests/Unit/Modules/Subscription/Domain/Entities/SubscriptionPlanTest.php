<?php

declare(strict_types=1);

use App\Modules\Subscription\Domain\Entities\SubscriptionPlan;
use App\Modules\Subscription\Domain\Enums\BillingPeriod;
use App\Modules\Subscription\Domain\Enums\FeatureKey;
use App\Shared\Domain\ValueObjects\Money;

function makeTestPlan(array $features): SubscriptionPlan
{
    return SubscriptionPlan::reconstitute(
        id: 1,
        slug: 'plus',
        nameRu: 'Plus',
        price: Money::fromMinorUnits(29_900),
        period: BillingPeriod::Month,
        features: $features,
        isActive: true,
    );
}

it('treats a null daily_likes value as unlimited', function (): void {
    $plan = makeTestPlan(['daily_likes' => null, 'super_likes_per_week' => 5, 'boosts_per_month' => 1, 'marketplace_commission_bps' => 500]);

    expect($plan->dailyLikesLimit())->toBeNull()
        ->and($plan->limitFor(FeatureKey::DailyLike))->toBeNull();
});

it('reads numeric limits from features', function (): void {
    $plan = makeTestPlan(['daily_likes' => 20, 'super_likes_per_week' => 1, 'boosts_per_month' => 0, 'marketplace_commission_bps' => 500]);

    expect($plan->dailyLikesLimit())->toBe(20)
        ->and($plan->superLikesPerWeekLimit())->toBe(1)
        ->and($plan->boostsPerMonthLimit())->toBe(0)
        ->and($plan->limitFor(FeatureKey::SuperLike))->toBe(1)
        ->and($plan->limitFor(FeatureKey::Boost))->toBe(0);
});

it('reads the marketplace commission rate from features', function (): void {
    $plan = makeTestPlan(['marketplace_commission_bps' => 400]);

    expect($plan->marketplaceCommissionBps())->toBe(400);
});

it('defaults the commission rate to 500 bps when missing from features', function (): void {
    $plan = makeTestPlan([]);

    expect($plan->marketplaceCommissionBps())->toBe(500);
});
