<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Providers;

use App\Modules\Marketplace\Application\Contracts\CommissionRateResolverInterface;
use App\Modules\Matching\Application\Contracts\SubscriptionFeatureGateInterface;
use App\Modules\Payment\Domain\Events\PaymentCanceled;
use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use App\Modules\Profile\Application\Contracts\SubscriptionFeatureGateInterface as ProfileSubscriptionFeatureGateInterface;
use App\Modules\Subscription\Domain\Repositories\FeatureUsageRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionPlanRepositoryInterface;
use App\Modules\Subscription\Domain\Repositories\SubscriptionRepositoryInterface;
use App\Modules\Subscription\Infrastructure\Adapters\MarketplaceCommissionRateResolver;
use App\Modules\Subscription\Infrastructure\Adapters\MatchingFeatureGateAdapter;
use App\Modules\Subscription\Infrastructure\Adapters\ProfileFeatureGateAdapter;
use App\Modules\Subscription\Infrastructure\Console\ProcessSubscriptionBillingCommand;
use App\Modules\Subscription\Infrastructure\Listeners\ActivateSubscriptionOnPaymentSucceeded;
use App\Modules\Subscription\Infrastructure\Listeners\HandleSubscriptionPaymentCanceled;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Repositories\EloquentFeatureUsageRepository;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Repositories\EloquentSubscriptionPlanRepository;
use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Repositories\EloquentSubscriptionRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriptionPlanRepositoryInterface::class, EloquentSubscriptionPlanRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
        $this->app->bind(FeatureUsageRepositoryInterface::class, EloquentFeatureUsageRepository::class);

        // Единственные места, где Subscription "знает" про Matching/Marketplace/Profile —
        // байндинг чужих Application-контрактов на свои реализации, см. docs/rules/01-backend.md.
        $this->app->bind(SubscriptionFeatureGateInterface::class, MatchingFeatureGateAdapter::class);
        $this->app->bind(ProfileSubscriptionFeatureGateInterface::class, ProfileFeatureGateAdapter::class);
        $this->app->bind(CommissionRateResolverInterface::class, MarketplaceCommissionRateResolver::class);
    }

    public function boot(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->listen(PaymentSucceeded::class, ActivateSubscriptionOnPaymentSucceeded::class);
        $dispatcher->listen(PaymentCanceled::class, HandleSubscriptionPaymentCanceled::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ProcessSubscriptionBillingCommand::class]);
        }
    }
}
