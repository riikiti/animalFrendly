<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Providers;

use App\Modules\Shop\Domain\Repositories\CartRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\CategoryRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories\EloquentCartRepository;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

final class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);
    }
}
