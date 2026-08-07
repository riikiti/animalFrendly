<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Providers;

use App\Modules\Marketplace\Application\Contracts\PaymentGatewayInterface;
use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Marketplace\Domain\Events\OrderRefunded;
use App\Modules\Payment\Application\Contracts\YookassaClientInterface;
use App\Modules\Payment\Domain\Repositories\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Repositories\PayoutRepositoryInterface;
use App\Modules\Payment\Infrastructure\Adapters\ShopPaymentGateway;
use App\Modules\Payment\Infrastructure\Adapters\SubscriptionBillingGateway;
use App\Modules\Payment\Infrastructure\Adapters\YookassaPaymentGateway;
use App\Modules\Payment\Infrastructure\External\NullYookassaClient;
use App\Modules\Payment\Infrastructure\External\YookassaClient;
use App\Modules\Payment\Infrastructure\Listeners\DispatchPayoutOnOrderCompleted;
use App\Modules\Payment\Infrastructure\Listeners\DispatchRefundOnOrderRefunded;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPaymentRepository;
use App\Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories\EloquentPayoutRepository;
use App\Modules\Shop\Application\Contracts\PaymentGatewayInterface as ShopPaymentGatewayInterface;
use App\Modules\Subscription\Application\Contracts\SubscriptionBillingGatewayInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->bind(PayoutRepositoryInterface::class, EloquentPayoutRepository::class);
        // Без реальных ключей (локальная разработка, E2E) — безопасный фоллбэк без сети,
        // тот же принцип, что MAIL_MAILER=log. См. NullYookassaClient.
        $this->app->bind(
            YookassaClientInterface::class,
            empty(config('yookassa.shop_id')) ? NullYookassaClient::class : YookassaClient::class,
        );

        // Единственные места, где Payment "знает" про Marketplace/Subscription — байндинг
        // чужих Application-контрактов на свои реализации, см. docs/rules/01-backend.md.
        $this->app->bind(PaymentGatewayInterface::class, YookassaPaymentGateway::class);
        $this->app->bind(ShopPaymentGatewayInterface::class, ShopPaymentGateway::class);
        $this->app->bind(SubscriptionBillingGatewayInterface::class, SubscriptionBillingGateway::class);
    }

    public function boot(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->listen(OrderCompleted::class, DispatchPayoutOnOrderCompleted::class);
        $dispatcher->listen(OrderRefunded::class, DispatchRefundOnOrderRefunded::class);
    }
}
