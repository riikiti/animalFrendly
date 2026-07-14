<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Queries\GetOrder;

use App\Modules\Marketplace\Domain\Entities\Order;
use App\Modules\Marketplace\Domain\Exceptions\NotOrderPartyException;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class GetOrderHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function handle(GetOrderQuery $query): Order
    {
        $order = $this->orders->findById(Id::fromString($query->orderId));

        if ($order === null) {
            throw OrderNotFoundException::forId($query->orderId);
        }

        $actingUserId = Id::fromString($query->actingUserId);

        if (! $order->isBuyer($actingUserId) && ! $order->isSeller($actingUserId)) {
            throw NotOrderPartyException::create();
        }

        return $order;
    }
}
