<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Repositories;

use App\Modules\Shop\Domain\Entities\ShopOrder;
use App\Shared\Domain\ValueObjects\Id;

interface ShopOrderRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(ShopOrder $order): void;

    public function findById(Id $id): ?ShopOrder;

    /**
     * Заказы пользователя в выбранной роли.
     *
     * @return array<int, ShopOrder>
     */
    public function listFor(Id $userId, string $role): array;

    /**
     * Оплаченные заказы, у которых вышел срок удержания — для автоподтверждения.
     *
     * @return array<int, ShopOrder>
     */
    public function listExpiredEscrow(): array;
}
