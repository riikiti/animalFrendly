<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Commands\CancelSubscription;

use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Modules\Subscription\Domain\Exceptions\NoActiveSubscriptionException;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class CancelSubscriptionHandler
{
    public function __construct(private readonly SubscriptionRepositoryInterface $subscriptions) {}

    public function handle(CancelSubscriptionCommand $command): Subscription
    {
        $subscription = $this->subscriptions->findActiveForUser(Id::fromString($command->userId));

        if ($subscription === null) {
            throw NoActiveSubscriptionException::create();
        }

        $subscription->cancelAutoRenew();
        $this->subscriptions->save($subscription);

        return $subscription;
    }
}
