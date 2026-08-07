<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Shop\Domain\Entities\Product;
use App\Modules\Shop\Domain\Enums\ProductStatus;
use App\Modules\Shop\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopProduct;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function nextIdentity(): Id
    {
        return Id::generate();
    }

    public function save(Product $product): void
    {
        ShopProduct::query()->updateOrCreate(
            ['id' => $product->id()->toString()],
            [
                'seller_id' => $product->sellerId()->toString(),
                'category_id' => $product->categoryId()->toString(),
                'title' => $product->title(),
                'description' => $product->description(),
                'price_amount' => $product->price()->minorUnits,
                'currency' => $product->price()->currency,
                'stock' => $product->stock(),
                'status' => $product->status()->value,
                'photo_url' => $product->photoUrl(),
            ],
        );
    }

    public function findById(Id $id): ?Product
    {
        $model = ShopProduct::query()->find($id->toString());

        return $model === null ? null : $this->toDomain($model);
    }

    public function listPublished(?Id $categoryId, ?string $query, int $limit): array
    {
        return ShopProduct::query()
            ->where('status', ProductStatus::Published->value)
            ->where('stock', '>', 0)
            ->when($categoryId !== null, fn ($builder) => $builder->where('category_id', $categoryId?->toString()))
            ->when(
                $query !== null && $query !== '',
                fn ($builder) => $builder->whereRaw('title ILIKE ?', ['%'.$query.'%']),
            )
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ShopProduct $model): Product => $this->toDomain($model))
            ->all();
    }

    public function listBySeller(Id $sellerId): array
    {
        return ShopProduct::query()
            ->where('seller_id', $sellerId->toString())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ShopProduct $model): Product => $this->toDomain($model))
            ->all();
    }

    private function toDomain(ShopProduct $model): Product
    {
        return Product::reconstitute(
            Id::fromString($model->id),
            Id::fromString($model->seller_id),
            Id::fromString($model->category_id),
            $model->title,
            $model->description,
            Money::fromMinorUnits($model->price_amount, $model->currency),
            $model->stock,
            ProductStatus::from($model->status),
            $model->photo_url,
        );
    }
}
