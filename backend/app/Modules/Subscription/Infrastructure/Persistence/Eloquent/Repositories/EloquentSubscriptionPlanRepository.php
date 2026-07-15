<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Subscription\Domain\Entities\SubscriptionPlan as DomainSubscriptionPlan;
use App\Modules\Subscription\Domain\Enums\BillingPeriod;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\SubscriptionPlan as EloquentSubscriptionPlan;
use App\Shared\Domain\ValueObjects\Money;

final class EloquentSubscriptionPlanRepository implements SubscriptionPlanRepositoryInterface
{
    public function findBySlug(string $slug): ?DomainSubscriptionPlan
    {
        $model = EloquentSubscriptionPlan::query()->where('slug', $slug)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(int $id): ?DomainSubscriptionPlan
    {
        $model = EloquentSubscriptionPlan::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function listActive(): array
    {
        return array_values(
            EloquentSubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('price_amount')
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentSubscriptionPlan $model): DomainSubscriptionPlan
    {
        return DomainSubscriptionPlan::reconstitute(
            id: (int) $model->id,
            slug: $model->slug,
            nameRu: $model->name_ru,
            price: Money::fromMinorUnits((int) $model->price_amount, $model->currency),
            period: BillingPeriod::from($model->period),
            features: (array) $model->features,
            isActive: (bool) $model->is_active,
        );
    }
}
