<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\ResolveDispute;

use App\Modules\Marketplace\Domain\Entities\Dispute;
use App\Modules\Marketplace\Domain\Enums\DisputeResolution;
use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Marketplace\Domain\Events\OrderRefunded;
use App\Modules\Marketplace\Domain\Exceptions\DisputeNotFoundException;
use App\Modules\Marketplace\Domain\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Marketplace\Domain\Exceptions\OrderNotFoundException;
use App\Modules\Marketplace\Domain\Repositories\DisputeRepositoryInterface;
use App\Modules\Marketplace\Domain\Repositories\OrderRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Авторизация (только admin/moderator) — на уровне Gate в контроллере, не здесь, см.
 * docs/rules/01-backend.md.
 */
final class ResolveDisputeHandler
{
    public function __construct(
        private readonly DisputeRepositoryInterface $disputes,
        private readonly OrderRepositoryInterface $orders,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(ResolveDisputeCommand $command): Dispute
    {
        $dispute = $this->disputes->findById(Id::fromString($command->disputeId));

        if ($dispute === null) {
            throw DisputeNotFoundException::forId($command->disputeId);
        }

        $order = $this->orders->findById($dispute->orderId());

        if ($order === null) {
            throw OrderNotFoundException::forId($dispute->orderId()->toString());
        }

        $resolvedBy = Id::fromString($command->resolvedBy);
        $resolution = DisputeResolution::from($command->resolution);

        return DB::transaction(function () use ($dispute, $order, $resolvedBy, $resolution): Dispute {
            $dispute->resolve($resolution, $resolvedBy);
            $this->disputes->save($dispute);

            if ($resolution === DisputeResolution::SellerWins) {
                $order->completeFromDispute();
                $this->orders->save($order, $resolvedBy, 'dispute_resolved:seller_wins');

                $payoutAmount = $order->payoutAmount();

                if ($payoutAmount === null) {
                    throw InvalidOrderStatusTransitionException::create();
                }

                $this->events->dispatch(new OrderCompleted($order->id(), $order->sellerId(), $payoutAmount, new DateTimeImmutable));
            } else {
                $order->refundFromDispute();
                $this->orders->save($order, $resolvedBy, 'dispute_resolved:buyer_wins');

                $this->events->dispatch(new OrderRefunded($order->id(), new DateTimeImmutable));
            }

            return $dispute;
        });
    }
}
