<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Repositories;

use App\Modules\Shop\Domain\Entities\ShopOrder;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

interface ShopOrderRepositoryInterface
{
    public function nextIdentity(): Id;

    /**
     * Заводит оформление — то, что оплачивается одним платежом, — и возвращает его id.
     * Заказы создаются уже под ним.
     */
    public function startCheckout(Id $buyerId): Id;

    /** Проставляет оформлению итоговую сумму, когда все заказы созданы. */
    public function setCheckoutAmount(Id $checkoutId, Money $amount): void;

    public function save(ShopOrder $order): void;

    public function findById(Id $id): ?ShopOrder;

    /**
     * Заказы пользователя в выбранной роли.
     *
     * @return array<int, ShopOrder>
     */
    public function listFor(Id $userId, string $role): array;

    /**
     * Заказы одного оформления — их покрывает один платёж.
     *
     * @return array<int, ShopOrder>
     */
    public function listByCheckout(Id $checkoutId): array;

    /**
     * Оплаченные заказы, у которых вышел срок удержания — для автоподтверждения.
     *
     * @return array<int, ShopOrder>
     */
    public function listExpiredEscrow(): array;
}
