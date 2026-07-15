<?php

declare(strict_types=1);

use App\Modules\Subscription\Application\Commands\SubscribeToPlan\SubscribeToPlanCommand;
use App\Modules\Subscription\Application\Commands\SubscribeToPlan\SubscribeToPlanHandler;
use App\Modules\Subscription\Application\Contracts\PaymentInitiationResult;
use App\Modules\Subscription\Application\Contracts\SubscriptionBillingGatewayInterface;
use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Modules\Subscription\Domain\Entities\SubscriptionPlan;
use App\Modules\Subscription\Domain\Enums\BillingPeriod;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Exceptions\AlreadySubscribedException;
use App\Modules\Subscription\Domain\Exceptions\PlanNotFoundException;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

function makePlusPlan(): SubscriptionPlan
{
    return SubscriptionPlan::reconstitute(
        id: 2,
        slug: 'plus',
        nameRu: 'Plus',
        price: Money::fromMinorUnits(29_900),
        period: BillingPeriod::Month,
        features: ['marketplace_commission_bps' => 500],
        isActive: true,
    );
}

it('creates a pending subscription and initiates the first payment', function (): void {
    $userId = Id::generate();
    $plan = makePlusPlan();

    $plans = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
    $plans->shouldReceive('findBySlug')->once()->with('plus')->andReturn($plan);

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findCurrentForUser')->once()->andReturn(null);
    $subscriptions->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $subscriptions->shouldReceive('save')->once()
        ->with(Mockery::on(fn (Subscription $s) => $s->status() === SubscriptionStatus::PendingPayment));

    $billingGateway = Mockery::mock(SubscriptionBillingGatewayInterface::class);
    $billingGateway->shouldReceive('initiateFirstPayment')->once()
        ->andReturn(new PaymentInitiationResult('https://yookassa.ru/pay/123', 'yk-123'));

    $handler = new SubscribeToPlanHandler($plans, $subscriptions, $billingGateway);
    $result = $handler->handle(new SubscribeToPlanCommand('plus', $userId->toString(), 'https://app.test/subscription/status'));

    expect($result->confirmationUrl)->toBe('https://yookassa.ru/pay/123')
        ->and($result->subscription->userId()->equals($userId))->toBeTrue();
});

it('rejects an unknown plan slug', function (): void {
    $plans = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
    $plans->shouldReceive('findBySlug')->once()->andReturn(null);

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $billingGateway = Mockery::mock(SubscriptionBillingGatewayInterface::class);

    $handler = new SubscribeToPlanHandler($plans, $subscriptions, $billingGateway);
    $handler->handle(new SubscribeToPlanCommand('nonexistent', Id::generate()->toString(), 'https://app.test/subscription/status'));
})->throws(PlanNotFoundException::class);

it('rejects subscribing when the user already has a current subscription', function (): void {
    $plan = makePlusPlan();

    $plans = Mockery::mock(SubscriptionPlanRepositoryInterface::class);
    $plans->shouldReceive('findBySlug')->once()->andReturn($plan);

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findCurrentForUser')->once()
        ->andReturn(Subscription::subscribe(Id::generate(), Id::generate(), 1));

    $billingGateway = Mockery::mock(SubscriptionBillingGatewayInterface::class);

    $handler = new SubscribeToPlanHandler($plans, $subscriptions, $billingGateway);
    $handler->handle(new SubscribeToPlanCommand('plus', Id::generate()->toString(), 'https://app.test/subscription/status'));
})->throws(AlreadySubscribedException::class);
