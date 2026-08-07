<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Services;

use App\Modules\Shop\Domain\Entities\Product;
use App\Modules\Shop\Domain\Exceptions\CannotBuyOwnProductException;
use App\Modules\Shop\Domain\Exceptions\CartFromSingleSellerException;
use App\Modules\Shop\Domain\Exceptions\ProductNotAvailableException;
use App\Modules\Shop\Domain\Exceptions\ProductNotFoundException;
use App\Modules\Shop\Domain\Repositories\CartRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ProductRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Корзина держит товары одного продавца — заказ уходит ему одному, на него считаются
 * комиссия и выплата. Товар другого продавца просим оформить отдельно.
 */
final class CartService
{
    public function __construct(
        private readonly CartRepositoryInterface $cart,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @return array{items: array<int, array{product: Product, quantity: int}>, seller_id: ?string, total: Money}
     */
    public function contents(Id $userId): array
    {
        $items = [];
        $total = Money::zero();
        $sellerId = null;

        foreach ($this->cart->itemsOf($userId) as $row) {
            $product = $this->products->findById(Id::fromString($row['product_id']));

            // Товар мог быть удалён продавцом — просто выкидываем строку из корзины.
            if ($product === null) {
                $this->cart->remove($userId, Id::fromString($row['product_id']));

                continue;
            }

            $items[] = ['product' => $product, 'quantity' => $row['quantity']];
            $total = $total->add(Money::fromMinorUnits($product->price()->minorUnits * $row['quantity']));
            $sellerId ??= $product->sellerId()->toString();
        }

        return ['items' => $items, 'seller_id' => $sellerId, 'total' => $total];
    }

    public function add(Id $userId, Id $productId, int $quantity): void
    {
        $product = $this->products->findById($productId);

        if ($product === null) {
            throw ProductNotFoundException::forId($productId->toString());
        }

        if ($product->sellerId()->equals($userId)) {
            throw CannotBuyOwnProductException::create();
        }

        if (! $product->isAvailable()) {
            throw ProductNotAvailableException::create();
        }

        $contents = $this->contents($userId);

        if ($contents['seller_id'] !== null && $contents['seller_id'] !== $product->sellerId()->toString()) {
            throw CartFromSingleSellerException::create();
        }

        $already = 0;
        foreach ($contents['items'] as $item) {
            if ($item['product']->id()->equals($productId)) {
                $already = $item['quantity'];
            }
        }

        if ($already + $quantity > $product->stock()) {
            throw ProductNotAvailableException::outOfStock($product->title(), $product->stock());
        }

        $this->cart->add($userId, $productId, $quantity);
    }

    public function setQuantity(Id $userId, Id $productId, int $quantity): void
    {
        $product = $this->products->findById($productId);

        if ($product !== null && $quantity > $product->stock()) {
            throw ProductNotAvailableException::outOfStock($product->title(), $product->stock());
        }

        $this->cart->setQuantity($userId, $productId, $quantity);
    }

    public function remove(Id $userId, Id $productId): void
    {
        $this->cart->remove($userId, $productId);
    }

    public function clear(Id $userId): void
    {
        $this->cart->clear($userId);
    }
}
