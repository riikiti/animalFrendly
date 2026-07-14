<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Providers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Marketplace\Domain\Repositories\DisputeRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\ListingRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Modules\Marketplace\Infrastructure\Console\AutoConfirmEscrowDealsCommand;
use App\Modules\Marketplace\Infrastructure\Listeners\CancelOrderOnPaymentCanceled;
use App\Modules\Marketplace\Infrastructure\Listeners\MarkOrderPaidOnPaymentSucceeded;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Repositories\EloquentDisputeRepository;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Repositories\EloquentListingRepository;
use App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrderRepository;
use App\Modules\Payment\Domain\Events\PaymentCanceled;
use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ListingRepositoryInterface::class, EloquentListingRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(DisputeRepositoryInterface::class, EloquentDisputeRepository::class);
    }

    public function boot(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->listen(PaymentSucceeded::class, MarkOrderPaidOnPaymentSucceeded::class);
        $dispatcher->listen(PaymentCanceled::class, CancelOrderOnPaymentCanceled::class);

        // Разрешение споров — RBAC через Gate, не через if ($user->accountType === ...) в
        // контроллере, см. docs/rules/01-backend.md.
        Gate::define('resolve-marketplace-disputes', static fn (IdentityUser $user): bool => in_array(
            $user->account_type,
            ['admin', 'moderator'],
            true,
        ));

        if ($this->app->runningInConsole()) {
            $this->commands([AutoConfirmEscrowDealsCommand::class]);
        }
    }
}
