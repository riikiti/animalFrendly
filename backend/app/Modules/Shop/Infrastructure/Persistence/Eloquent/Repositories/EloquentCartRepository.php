<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Shop\Domain\Repositories\CartRepositoryInterface;
use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopCartItem;
use App\Shared\Domain\ValueObjects\Id;

final class EloquentCartRepository implements CartRepositoryInterface
{
    public function itemsOf(Id $userId): array
    {
        return ShopCartItem::query()
            ->where('user_id', $userId->toString())
            ->orderBy('created_at')
            ->get()
            ->map(static fn (ShopCartItem $item): array => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])
            ->all();
    }

    public function add(Id $userId, Id $productId, int $quantity): void
    {
        $item = ShopCartItem::query()
            ->where('user_id', $userId->toString())
            ->where('product_id', $productId->toString())
            ->first();

        if ($item === null) {
            ShopCartItem::query()->create([
                'user_id' => $userId->toString(),
                'product_id' => $productId->toString(),
                'quantity' => $quantity,
            ]);

            return;
        }

        $item->update(['quantity' => $item->quantity + $quantity]);
    }

    public function setQuantity(Id $userId, Id $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($userId, $productId);

            return;
        }

        ShopCartItem::query()
            ->where('user_id', $userId->toString())
            ->where('product_id', $productId->toString())
            ->update(['quantity' => $quantity]);
    }

    public function remove(Id $userId, Id $productId): void
    {
        ShopCartItem::query()
            ->where('user_id', $userId->toString())
            ->where('product_id', $productId->toString())
            ->delete();
    }

    public function clear(Id $userId): void
    {
        ShopCartItem::query()->where('user_id', $userId->toString())->delete();
    }
}
