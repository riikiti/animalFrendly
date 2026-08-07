<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Repositories;

use App\Shared\Domain\ValueObjects\Id;

interface CartRepositoryInterface
{
    /**
     * Строки корзины: id товара и количество.
     *
     * @return array<int, array{product_id: string, quantity: int}>
     */
    public function itemsOf(Id $userId): array;

    public function add(Id $userId, Id $productId, int $quantity): void;

    public function setQuantity(Id $userId, Id $productId, int $quantity): void;

    public function remove(Id $userId, Id $productId): void;

    public function clear(Id $userId): void;
}
