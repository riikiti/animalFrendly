<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Repositories;

use App\Modules\Subscription\Domain\Entities\SubscriptionPlan;

interface SubscriptionPlanRepositoryInterface
{
    public function findBySlug(string $slug): ?SubscriptionPlan;

    public function findById(int $id): ?SubscriptionPlan;

    /**
     * @return list<SubscriptionPlan>
     */
    public function listActive(): array;
}
