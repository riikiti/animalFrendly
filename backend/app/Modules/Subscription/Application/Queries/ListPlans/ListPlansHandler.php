<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Queries\ListPlans;

use App\Modules\Subscription\Domain\Entities\SubscriptionPlan;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;

final class ListPlansHandler
{
    public function __construct(private readonly SubscriptionPlanRepositoryInterface $plans) {}

    /**
     * @return list<SubscriptionPlan>
     */
    public function handle(ListPlansQuery $query): array
    {
        return $this->plans->listActive();
    }
}
