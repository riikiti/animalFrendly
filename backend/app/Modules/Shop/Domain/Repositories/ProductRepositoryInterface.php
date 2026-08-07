<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Repositories;

use App\Modules\Shop\Domain\Entities\Product;
use App\Shared\Domain\ValueObjects\Id;

interface ProductRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Product $product): void;

    public function findById(Id $id): ?Product;

    /**
     * Витрина: только опубликованные товары в наличии.
     *
     * @return array<int, Product>
     */
    public function listPublished(?Id $categoryId, ?string $query, int $limit): array;

    /**
     * @return array<int, Product>
     */
    public function listBySeller(Id $sellerId): array;
}
