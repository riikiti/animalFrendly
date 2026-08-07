<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Providers;

use App\Modules\Payment\Domain\Events\PaymentSucceeded;
use App\Modules\Shop\Domain\Repositories\CartRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\CategoryRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ShopOrderRepositoryInterface;
use App\Modules\Shop\Infrastructure\Listeners\MarkShopOrderPaidOnPaymentSucceeded;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories\EloquentCartRepository;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories\EloquentShopOrderRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
        $this->app->bind(ShopOrderRepositoryInterface::class, EloquentShopOrderRepository::class);
    }

    public function boot(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->listen(PaymentSucceeded::class, MarkShopOrderPaidOnPaymentSucceeded::class);
    }
}
