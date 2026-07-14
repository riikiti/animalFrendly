<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Providers;

use App\Modules\Marketplace\Application\Contracts\PaymentGatewayInterface;
use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Marketplace\Domain\Events\OrderRefunded;
use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Repositories\PayoutRepositoryInterface;
use App\Modules\Payment\Infrastructure\Adapters\YookassaPaymentGateway;
use App\Modules\Payment\Infrastructure\External\YookassaClient;
use App\Modules\Payment\Infrastructure\Listeners\DispatchPayoutOnOrderCompleted;
use App\Modules\Payment\Infrastructure\Listeners\DispatchRefundOnOrderRefunded;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentRepository;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayoutRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(PayoutRepositoryInterface::class, EloquentPayoutRepository::class);
        $this->app->bind(YookassaClientInterface::class, YookassaClient::class);

        // Единственное место, где Payment "знает" про Marketplace — байндинг чужого
        // Application-контракта на свою реализацию, см. docs/rules/01-backend.md.
        $this->app->bind(PaymentGatewayInterface::class, YookassaPaymentGateway::class);
    }

    public function boot(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->listen(OrderCompleted::class, DispatchPayoutOnOrderCompleted::class);
        $dispatcher->listen(OrderRefunded::class, DispatchRefundOnOrderRefunded::class);
    }
}
