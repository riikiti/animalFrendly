<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Payment\Infrastructure\Jobs\ChargeRecurringSubscriptionJob;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\Subscription as EloquentSubscription;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\SubscriptionPlan as EloquentSubscriptionPlan;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

function seedBillingPlan(): EloquentSubscriptionPlan
{
    return EloquentSubscriptionPlan::query()->create([
        'slug' => 'plus',
        'name_ru' => 'Plus',
        'price_amount' => 29_900,
        'currency' => 'RUB',
        'period' => 'month',
        'features' => ['marketplace_commission_bps' => 500],
        'is_active' => true,
    ]);
}

function createBillingSubscription(int $planId, string $status, array $overrides = []): EloquentSubscription
{
    return EloquentSubscription::query()->create(array_merge([
        'id' => (string) Str::ulid(),
        'user_id' => User::factory()->create()->id,
        'plan_id' => $planId,
        'status' => $status,
        'started_at' => now()->subMonth(),
        'auto_renew' => true,
        'yookassa_payment_method_id' => 'pm-billing-test',
    ], $overrides));
}

it('dispatches a recurring charge for an active subscription past its period end', function (): void {
    Bus::fake();
    $plan = seedBillingPlan();

    $subscription = createBillingSubscription($plan->id, 'active', [
        'current_period_ends_at' => now()->subDay(),
    ]);

    Artisan::call('subscriptions:process-billing');

    Bus::assertDispatched(ChargeRecurringSubscriptionJob::class, fn ($job) => $job->subscriptionId === $subscription->id);
});

it('expires an active subscription whose auto-renew was cancelled once the period ends', function (): void {
    Bus::fake();
    $plan = seedBillingPlan();

    $subscription = createBillingSubscription($plan->id, 'active', [
        'current_period_ends_at' => now()->subDay(),
        'auto_renew' => false,
        'canceled_at' => now()->subWeek(),
    ]);

    Artisan::call('subscriptions:process-billing');

    expect(EloquentSubscription::query()->find($subscription->id)->status)->toBe('expired');
    Bus::assertNotDispatched(ChargeRecurringSubscriptionJob::class);
});

it('retries a past_due subscription still within the grace period', function (): void {
    Bus::fake();
    config(['subscription.past_due_grace_days' => 3]);
    $plan = seedBillingPlan();

    $subscription = createBillingSubscription($plan->id, 'past_due', [
        'current_period_ends_at' => now()->subDay(),
    ]);

    Artisan::call('subscriptions:process-billing');

    Bus::assertDispatched(ChargeRecurringSubscriptionJob::class, fn ($job) => $job->subscriptionId === $subscription->id);
    expect(EloquentSubscription::query()->find($subscription->id)->status)->toBe('past_due');
});

it('expires a past_due subscription once the grace period has elapsed', function (): void {
    Bus::fake();
    config(['subscription.past_due_grace_days' => 3]);
    $plan = seedBillingPlan();

    $subscription = createBillingSubscription($plan->id, 'past_due', [
        'current_period_ends_at' => now()->subDays(4),
    ]);

    Artisan::call('subscriptions:process-billing');

    expect(EloquentSubscription::query()->find($subscription->id)->status)->toBe('expired');
    Bus::assertNotDispatched(ChargeRecurringSubscriptionJob::class);
});
