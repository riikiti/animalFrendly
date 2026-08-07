<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Entities;

use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

/**
 * Позиция заказа. Название и цена скопированы в момент покупки: продавец может потом
 * поменять их у товара, а в оформленном заказе должно остаться то, что покупали.
 */
final class ShopOrderItem
{
    public function __construct(
        private readonly Id $id,
        private readonly Id $productId,
        private readonly string $title,
        private readonly Money $price,
        private readonly int $quantity,
    ) {}

    public function lineTotal(): Money
    {
        return Money::fromMinorUnits($this->price->minorUnits * $this->quantity, $this->price->currency);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function productId(): Id
    {
        return $this->productId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }
}
