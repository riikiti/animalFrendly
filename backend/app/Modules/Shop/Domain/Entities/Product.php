<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Entities;

use App\Modules\Shop\Domain\Enums\ProductStatus;
use App\Modules\Shop\Domain\Exceptions\ProductNotAvailableException;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class Product
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $sellerId,
        private Id $categoryId,
        private string $title,
        private ?string $description,
        private Money $price,
        private int $stock,
        private ProductStatus $status,
        private ?string $photoUrl,
    ) {}

    public static function create(
        Id $id,
        Id $sellerId,
        Id $categoryId,
        string $title,
        ?string $description,
        Money $price,
        int $stock,
        ?string $photoUrl = null,
    ): self {
        return new self($id, $sellerId, $categoryId, $title, $description, $price, $stock, ProductStatus::Draft, $photoUrl);
    }

    public static function reconstitute(
        Id $id,
        Id $sellerId,
        Id $categoryId,
        string $title,
        ?string $description,
        Money $price,
        int $stock,
        ProductStatus $status,
        ?string $photoUrl,
    ): self {
        return new self($id, $sellerId, $categoryId, $title, $description, $price, $stock, $status, $photoUrl);
    }

    public function publish(): void
    {
        $this->status = ProductStatus::Published;
    }

    public function archive(): void
    {
        $this->status = ProductStatus::Archived;
    }

    public function update(string $title, ?string $description, Money $price, int $stock, Id $categoryId, ?string $photoUrl): void
    {
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
        $this->stock = $stock;
        $this->categoryId = $categoryId;
        $this->photoUrl = $photoUrl;
    }

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::Published && $this->stock > 0;
    }

    /**
     * Списывает количество при оформлении заказа.
     *
     * @throws ProductNotAvailableException
     */
    public function takeFromStock(int $quantity): void
    {
        if ($this->status !== ProductStatus::Published) {
            throw ProductNotAvailableException::create();
        }

        if ($quantity > $this->stock) {
            throw ProductNotAvailableException::outOfStock($this->title, $this->stock);
        }

        $this->stock -= $quantity;
    }

    /** Возврат количества, когда заказ отменён или деньги вернулись покупателю. */
    public function returnToStock(int $quantity): void
    {
        $this->stock += $quantity;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function sellerId(): Id
    {
        return $this->sellerId;
    }

    public function categoryId(): Id
    {
        return $this->categoryId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function stock(): int
    {
        return $this->stock;
    }

    public function status(): ProductStatus
    {
        return $this->status;
    }

    public function photoUrl(): ?string
    {
        return $this->photoUrl;
    }
}
