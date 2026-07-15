<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Commands\SubscribeToPlan;

use App\Modules\Subscription\Application\Contracts\SubscriptionBillingGatewayInterface;
use App\Modules\Subscription\Domain\Entities\Subscription;
use App\Modules\Subscription\Domain\Exceptions\AlreadySubscribedException;
use App\Modules\Subscription\Domain\Exceptions\PlanNotFoundException;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\DB;

final class SubscribeToPlanHandler
{
    public function __construct(
        private readonly SubscriptionPlanRepositoryInterface $plans,
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly SubscriptionBillingGatewayInterface $billingGateway,
    ) {}

    public function handle(SubscribeToPlanCommand $command): SubscribeToPlanResult
    {
        $plan = $this->plans->findBySlug($command->planSlug);

        if ($plan === null || ! $plan->isActive()) {
            throw PlanNotFoundException::forSlug($command->planSlug);
        }

        $userId = Id::fromString($command->userId);

        if ($this->subscriptions->findCurrentForUser($userId) !== null) {
            throw AlreadySubscribedException::create();
        }

        return DB::transaction(function () use ($plan, $userId, $command): SubscribeToPlanResult {
            $subscription = Subscription::subscribe($this->subscriptions->nextIdentity(), $userId, $plan->id());
            $this->subscriptions->save($subscription);

            $result = $this->billingGateway->initiateFirstPayment($subscription->id(), $plan->price(), $command->returnUrl);

            return new SubscribeToPlanResult($subscription, $result->confirmationUrl);
        });
    }
}
