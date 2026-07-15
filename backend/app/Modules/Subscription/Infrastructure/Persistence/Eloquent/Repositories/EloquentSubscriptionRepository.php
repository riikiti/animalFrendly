<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Subscription\Domain\Entities\Subscription as DomainSubscription;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\Subscription as EloquentSubscription;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(DomainSubscription $subscription): void
    {
        EloquentSubscription::query()->updateOrCreate(
            ['id' => $subscription->id()->toString()],
            [
                'user_id' => $subscription->userId()->toString(),
                'plan_id' => $subscription->planId(),
                'status' => $subscription->status()->value,
                'started_at' => $subscription->startedAt(),
                'current_period_ends_at' => $subscription->currentPeriodEndsAt(),
                'auto_renew' => $subscription->autoRenew(),
                'canceled_at' => $subscription->canceledAt(),
                'yookassa_payment_method_id' => $subscription->yookassaPaymentMethodId(),
            ],
        );
    }

    public function findById(Id $id): ?DomainSubscription
    {
        $model = EloquentSubscription::query()->find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function findCurrentForUser(Id $userId): ?DomainSubscription
    {
        $model = EloquentSubscription::query()
            ->where('user_id', $userId->toString())
            ->whereIn('status', [
                SubscriptionStatus::PendingPayment->value,
                SubscriptionStatus::Active->value,
                SubscriptionStatus::PastDue->value,
            ])
            ->latest('created_at')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findActiveForUser(Id $userId): ?DomainSubscription
    {
        $model = EloquentSubscription::query()
            ->where('user_id', $userId->toString())
            ->where('status', SubscriptionStatus::Active->value)
            ->latest('created_at')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findDueForBilling(DateTimeImmutable $now): array
    {
        return array_values(
            EloquentSubscription::query()
                ->where('status', SubscriptionStatus::Active->value)
                ->where('auto_renew', true)
                ->where('current_period_ends_at', '<=', $now)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findEndedWithoutRenewal(DateTimeImmutable $now): array
    {
        return array_values(
            EloquentSubscription::query()
                ->where('status', SubscriptionStatus::Active->value)
                ->where('auto_renew', false)
                ->where('current_period_ends_at', '<=', $now)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    public function findPastDue(): array
    {
        return array_values(
            EloquentSubscription::query()
                ->where('status', SubscriptionStatus::PastDue->value)
                ->get()
                ->map($this->toDomain(...))
                ->all(),
        );
    }

    private function toDomain(EloquentSubscription $model): DomainSubscription
    {
        return DomainSubscription::reconstitute(
            id: Id::fromString($model->id),
            userId: Id::fromString($model->user_id),
            planId: (int) $model->plan_id,
            status: SubscriptionStatus::from($model->status),
            startedAt: $model->started_at?->toDateTimeImmutable(),
            currentPeriodEndsAt: $model->current_period_ends_at?->toDateTimeImmutable(),
            autoRenew: (bool) $model->auto_renew,
            canceledAt: $model->canceled_at?->toDateTimeImmutable(),
            yookassaPaymentMethodId: $model->yookassa_payment_method_id,
        );
    }
}
