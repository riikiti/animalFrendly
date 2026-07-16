<?php

declare(strict_types=1);

use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Modules\Subscription\Infrastructure\Adapters\ProfileFeatureGateAdapter;
use App\Shared\Domain\ValueObjects\Id;

it('reports unlimited pets when the user has an active subscription', function (): void {
    $userId = Id::generate();

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->with($userId)->andReturn(
        Subscription::reconstitute(
            id: Id::generate(),
            userId: $userId,
            planId: 1,
            status: SubscriptionStatus::Active,
            startedAt: new DateTimeImmutable,
            currentPeriodEndsAt: new DateTimeImmutable('+1 month'),
            autoRenew: true,
            canceledAt: null,
            yookassaPaymentMethodId: null,
        ),
    );

    $adapter = new ProfileFeatureGateAdapter($subscriptions);

    expect($adapter->hasUnlimitedPets($userId))->toBeTrue();
});

it('reports no unlimited pets without an active subscription', function (): void {
    $userId = Id::generate();

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->with($userId)->andReturn(null);

    $adapter = new ProfileFeatureGateAdapter($subscriptions);

    expect($adapter->hasUnlimitedPets($userId))->toBeFalse();
});
