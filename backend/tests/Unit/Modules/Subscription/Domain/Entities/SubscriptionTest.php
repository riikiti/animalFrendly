<?php

declare(strict_types=1);

use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Exceptions\InvalidSubscriptionStatusTransitionException;
use App\Shared\Domain\ValueObjects\Id;

function makeTestSubscription(): Subscription
{
    return Subscription::subscribe(Id::generate(), Id::generate(), 2);
}

it('is created as pending_payment', function (): void {
    expect(makeTestSubscription()->status())->toBe(SubscriptionStatus::PendingPayment);
});

it('activates from pending_payment, storing period end and payment method', function (): void {
    $subscription = makeTestSubscription();
    $periodEnd = new DateTimeImmutable('+1 month');

    $subscription->activate($periodEnd, 'pm-123');

    expect($subscription->status())->toBe(SubscriptionStatus::Active)
        ->and($subscription->startedAt())->not->toBeNull()
        ->and($subscription->currentPeriodEndsAt())->toBe($periodEnd)
        ->and($subscription->yookassaPaymentMethodId())->toBe('pm-123');
});

it('re-activates from past_due without resetting started_at', function (): void {
    $subscription = makeTestSubscription();
    $firstPeriodEnd = new DateTimeImmutable('+1 month');
    $subscription->activate($firstPeriodEnd, 'pm-123');
    $subscription->markPastDue();

    $startedAt = $subscription->startedAt();
    $newPeriodEnd = new DateTimeImmutable('+2 months');
    $subscription->activate($newPeriodEnd, null);

    expect($subscription->status())->toBe(SubscriptionStatus::Active)
        ->and($subscription->startedAt())->toBe($startedAt)
        ->and($subscription->currentPeriodEndsAt())->toBe($newPeriodEnd)
        ->and($subscription->yookassaPaymentMethodId())->toBe('pm-123');
});

it('cannot activate an already active subscription', function (): void {
    $subscription = makeTestSubscription();
    $subscription->activate(new DateTimeImmutable('+1 month'), 'pm-123');

    $subscription->activate(new DateTimeImmutable('+1 month'), 'pm-123');
})->throws(InvalidSubscriptionStatusTransitionException::class);

it('renews an active subscription', function (): void {
    $subscription = makeTestSubscription();
    $subscription->activate(new DateTimeImmutable('+1 month'), 'pm-123');

    $newPeriodEnd = new DateTimeImmutable('+2 months');
    $subscription->renew($newPeriodEnd);

    expect($subscription->currentPeriodEndsAt())->toBe($newPeriodEnd);
});

it('cannot renew a subscription that is not active', function (): void {
    makeTestSubscription()->renew(new DateTimeImmutable('+1 month'));
})->throws(InvalidSubscriptionStatusTransitionException::class);

it('marks an active subscription past_due', function (): void {
    $subscription = makeTestSubscription();
    $subscription->activate(new DateTimeImmutable('+1 month'), 'pm-123');

    $subscription->markPastDue();

    expect($subscription->status())->toBe(SubscriptionStatus::PastDue);
});

it('expires from pending_payment, past_due or active', function (): void {
    $subscription = makeTestSubscription();
    $subscription->expire();

    expect($subscription->status())->toBe(SubscriptionStatus::Expired);
});

it('cancels auto-renew on an active subscription, keeping it active', function (): void {
    $subscription = makeTestSubscription();
    $subscription->activate(new DateTimeImmutable('+1 month'), 'pm-123');

    $subscription->cancelAutoRenew();

    expect($subscription->status())->toBe(SubscriptionStatus::Active)
        ->and($subscription->autoRenew())->toBeFalse()
        ->and($subscription->canceledAt())->not->toBeNull();
});

it('cannot cancel auto-renew before the subscription is active', function (): void {
    makeTestSubscription()->cancelAutoRenew();
})->throws(InvalidSubscriptionStatusTransitionException::class);
