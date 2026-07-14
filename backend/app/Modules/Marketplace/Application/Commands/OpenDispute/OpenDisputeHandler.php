<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\OpenDispute;

use App\Modules\Marketplace\Domain\Entities\Dispute;
use App\Modules\Marketplace\Domain\Exceptions\DisputeAlreadyOpenException;
use App\Modules\Marketplace\Domain\Exceptions\NotOrderPartyException;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\DisputeRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\DB;

final class OpenDisputeHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DisputeRepositoryInterface $disputes,
    ) {}

    public function handle(OpenDisputeCommand $command): Dispute
    {
        $order = $this->orders->findById(Id::fromString($command->orderId));

        if ($order === null) {
            throw OrderNotFoundException::forId($command->orderId);
        }

        $actingUserId = Id::fromString($command->actingUserId);

        if (! $order->isBuyer($actingUserId) && ! $order->isSeller($actingUserId)) {
            throw NotOrderPartyException::create();
        }

        if ($this->disputes->findByOrderId($order->id()) !== null) {
            throw DisputeAlreadyOpenException::create();
        }

        return DB::transaction(function () use ($order, $actingUserId, $command): Dispute {
            $order->openDispute();
            $this->orders->save($order, $actingUserId, 'dispute_opened');

            $dispute = Dispute::open($this->disputes->nextIdentity(), $order->id(), $actingUserId, $command->reason);
            $this->disputes->save($dispute);

            return $dispute;
        });
    }
}
