<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Queries\ListMyOrders;

use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListMyOrdersHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /**
     * @return list<Order>
     */
    public function handle(ListMyOrdersQuery $query): array
    {
        $userId = Id::fromString($query->userId);

        return $query->role === 'seller'
            ? $this->orders->findBySeller($userId)
            : $this->orders->findByBuyer($userId);
    }
}
