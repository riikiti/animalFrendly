<?php

declare(strict_types=1);

use App\Modules\Subscription\Application\Commands\CancelSubscription\CancelSubscriptionCommand;
use App\Modules\Subscription\Application\Commands\CancelSubscription\CancelSubscriptionHandler;
use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Modules\Subscription\Domain\Exceptions\NoActiveSubscriptionException;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

it('turns off auto-renew on the active subscription', function (): void {
    $userId = Id::generate();
    $subscription = Subscription::subscribe(Id::generate(), $userId, 2);
    $subscription->activate(new DateTimeImmutable('+1 month'), 'pm-123');

    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->andReturn($subscription);
    $subscriptions->shouldReceive('save')->once()
        ->with(Mockery::on(fn (Subscription $s) => ! $s->autoRenew()));

    $handler = new CancelSubscriptionHandler($subscriptions);
    $result = $handler->handle(new CancelSubscriptionCommand($userId->toString()));

    expect($result->autoRenew())->toBeFalse();
});

it('rejects cancelling when there is no active subscription', function (): void {
    $subscriptions = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subscriptions->shouldReceive('findActiveForUser')->once()->andReturn(null);

    $handler = new CancelSubscriptionHandler($subscriptions);
    $handler->handle(new CancelSubscriptionCommand(Id::generate()->toString()));
})->throws(NoActiveSubscriptionException::class);
