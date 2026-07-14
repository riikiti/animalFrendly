<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Repositories;

use App\Modules\Marketplace\Domain\Entities\Order;
use App\Shared\Domain\ValueObjects\Id;

interface OrderRepositoryInterface
{
    public function nextIdentity(): Id;

    /**
     * Сохраняет заказ и, если статус изменился с момента загрузки (findById), пишет запись
     * в order_status_history в той же транзакции — правило аудита, см.
     * docs/database/00-conventions.md.
     */
    public function save(Order $order, ?Id $actorUserId = null, ?string $reason = null): void;

    public function findById(Id $id): ?Order;

    /**
     * @return list<Order>
     */
    public function findByBuyer(Id $buyerId): array;

    /**
     * @return list<Order>
     */
    public function findBySeller(Id $sellerId): array;

    /**
     * Заказы paid_escrow с истёкшим сроком удержания — для джобы авто-подтверждения.
     *
     * @return list<Order>
     */
    public function findEscrowExpired(): array;
}
