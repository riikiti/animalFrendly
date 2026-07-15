<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Queries\GetMySubscription;

use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class GetMySubscriptionHandler
{
    public function __construct(private readonly SubscriptionRepositoryInterface $subscriptions) {}

    public function handle(GetMySubscriptionQuery $query): ?Subscription
    {
        return $this->subscriptions->findCurrentForUser(Id::fromString($query->userId));
    }
}
