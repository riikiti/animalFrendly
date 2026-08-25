<?php

declare(strict_types=1);

namespace App\Modules\Shop\Application\Services;

use App\Modules\Shop\Domain\Entities\Product;
use App\Modules\Shop\Domain\Exceptions\CannotBuyOwnProductException;
use App\Modules\Shop\Domain\Exceptions\ProductNotAvailableException;
use App\Modules\Shop\Domain\Exceptions\ProductNotFoundException;
use App\Modules\Shop\Domain\Repositories\CartRepositoryInterface;
use App\Modules\Shop\Domain\Repositories\ProductRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Корзина может держать товары разных продавцов. При оформлении она разъезжается на
 * отдельные заказы — по одному на продавца, потому что комиссия, эскроу и выплата
 * считаются на конкретного получателя денег. Оплата при этом одна, см. CheckoutService.
 */
final class CartService
{
    public function __construct(
        private readonly CartRepositoryInterface $cart,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @return array{
     *     items: array<int, array{product: Product, quantity: int}>,
     *     groups: array<int, array{seller_id: string, items: array<int, array{product: Product, quantity: int}>, total: Money}>,
     *     total: Money
     * }
     */
    public function contents(Id $userId): array
    {
        $items = [];
        $total = Money::zero();

        foreach ($this->cart->itemsOf($userId) as $row) {
            $product = $this->products->findById(Id::fromString($row['product_id']));

            // Товар мог быть удалён продавцом — просто выкидываем строку из корзины.
            if ($product === null) {
                $this->cart->remove($userId, Id::fromString($row['product_id']));

                continue;
            }

            $items[] = ['product' => $product, 'quantity' => $row['quantity']];
            $total = $total->add(Money::fromMinorUnits($product->price()->minorUnits * $row['quantity']));
        }

        return ['items' => $items, 'groups' => $this->groupBySeller($items), 'total' => $total];
    }

    /**
     * @param  array<int, array{product: Product, quantity: int}>  $items
     * @return array<int, array{seller_id: string, items: array<int, array{product: Product, quantity: int}>, total: Money}>
     */
    public function groupBySeller(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            $sellerId = $item['product']->sellerId()->toString();

            $groups[$sellerId] ??= ['seller_id' => $sellerId, 'items' => [], 'total' => Money::zero()];
            $groups[$sellerId]['items'][] = $item;
            $groups[$sellerId]['total'] = $groups[$sellerId]['total']->add(
                Money::fromMinorUnits($item['product']->price()->minorUnits * $item['quantity']),
            );
        }

        return array_values($groups);
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

        $already = 0;
        foreach ($this->contents($userId)['items'] as $item) {
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
