<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Entities;

use App\Modules\Marketplace\Domain\Enums\OrderStatus;
use App\Modules\Marketplace\Domain\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Marketplace\Domain\Exceptions\OrderAlreadyConfirmedException;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use DateInterval;
use DateTimeImmutable;

/**
 * State machine сделки — единственное место в кодовой базе, где меняется orders.status.
 * См. docs/plan/08-flow-marketplace-escrow.md и docs/rules/04-payments-escrow.md.
 */
final class Order
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $listingId,
        private readonly Id $buyerId,
        private readonly Id $sellerId,
        private readonly Money $amount,
        private OrderStatus $status,
        private ?Money $commissionAmount,
        private ?Money $payoutAmount,
        private ?DateTimeImmutable $buyerConfirmedAt,
        private ?DateTimeImmutable $sellerConfirmedAt,
        private ?DateTimeImmutable $escrowHoldUntil,
    ) {}

    public static function create(Id $id, Id $listingId, Id $buyerId, Id $sellerId, Money $amount): self
    {
        return new self($id, $listingId, $buyerId, $sellerId, $amount, OrderStatus::PendingPayment, null, null, null, null, null);
    }

    public static function reconstitute(
        Id $id,
        Id $listingId,
        Id $buyerId,
        Id $sellerId,
        Money $amount,
        OrderStatus $status,
        ?Money $commissionAmount,
        ?Money $payoutAmount,
        ?DateTimeImmutable $buyerConfirmedAt,
        ?DateTimeImmutable $sellerConfirmedAt,
        ?DateTimeImmutable $escrowHoldUntil,
    ): self {
        return new self(
            $id, $listingId, $buyerId, $sellerId, $amount, $status,
            $commissionAmount, $payoutAmount, $buyerConfirmedAt, $sellerConfirmedAt, $escrowHoldUntil,
        );
    }

    public function markPaid(Money $commission, int $escrowHoldDays): void
    {
        $this->assertStatus(OrderStatus::PendingPayment);

        $this->commissionAmount = $commission;
        $this->payoutAmount = $this->amount->subtract($commission);
        $this->escrowHoldUntil = (new DateTimeImmutable)->add(new DateInterval("P{$escrowHoldDays}D"));
        $this->status = OrderStatus::PaidEscrow;
    }

    public function confirmByBuyer(): void
    {
        $this->assertStatus(OrderStatus::PaidEscrow);

        if ($this->buyerConfirmedAt !== null) {
            throw OrderAlreadyConfirmedException::create();
        }

        $this->buyerConfirmedAt = new DateTimeImmutable;
        $this->completeIfBothConfirmed();
    }

    public function confirmBySeller(): void
    {
        $this->assertStatus(OrderStatus::PaidEscrow);

        if ($this->sellerConfirmedAt !== null) {
            throw OrderAlreadyConfirmedException::create();
        }

        $this->sellerConfirmedAt = new DateTimeImmutable;
        $this->completeIfBothConfirmed();
    }

    public function openDispute(): void
    {
        $this->assertStatus(OrderStatus::PaidEscrow);
        $this->status = OrderStatus::Disputed;
    }

    public function completeFromDispute(): void
    {
        $this->assertStatus(OrderStatus::Disputed);
        $this->status = OrderStatus::Completed;
    }

    public function refundFromDispute(): void
    {
        $this->assertStatus(OrderStatus::Disputed);
        $this->status = OrderStatus::Refunded;
    }

    public function autoConfirm(): void
    {
        $this->assertStatus(OrderStatus::PaidEscrow);
        $this->status = OrderStatus::Completed;
    }

    public function cancel(): void
    {
        $this->assertStatus(OrderStatus::PendingPayment);
        $this->status = OrderStatus::Cancelled;
    }

    private function completeIfBothConfirmed(): void
    {
        if ($this->buyerConfirmedAt !== null && $this->sellerConfirmedAt !== null) {
            $this->status = OrderStatus::Completed;
        }
    }

    private function assertStatus(OrderStatus $expected): void
    {
        if ($this->status !== $expected) {
            throw InvalidOrderStatusTransitionException::create();
        }
    }

    public function isBuyer(Id $userId): bool
    {
        return $this->buyerId->equals($userId);
    }

    public function isSeller(Id $userId): bool
    {
        return $this->sellerId->equals($userId);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function listingId(): Id
    {
        return $this->listingId;
    }

    public function buyerId(): Id
    {
        return $this->buyerId;
    }

    public function sellerId(): Id
    {
        return $this->sellerId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function commissionAmount(): ?Money
    {
        return $this->commissionAmount;
    }

    public function payoutAmount(): ?Money
    {
        return $this->payoutAmount;
    }

    public function buyerConfirmedAt(): ?DateTimeImmutable
    {
        return $this->buyerConfirmedAt;
    }

    public function sellerConfirmedAt(): ?DateTimeImmutable
    {
        return $this->sellerConfirmedAt;
    }

    public function escrowHoldUntil(): ?DateTimeImmutable
    {
        return $this->escrowHoldUntil;
    }
}
