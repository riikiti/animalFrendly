<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Entities;

use App\Modules\Shop\Domain\Enums\DeliveryMethod;
use App\Modules\Shop\Domain\Enums\ShopOrderStatus;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use DateTimeImmutable;
use DomainException;

/**
 * Заказ в магазине товаров. Состояния и эскроу те же, что у сделки маркетплейса:
 * деньги держит площадка, пока обе стороны не подтвердят, см. docs/rules/04-payments-escrow.md.
 * Отличий два — позиций в заказе несколько, и есть доставка.
 */
final class ShopOrder
{
    /**
     * @param  array<int, ShopOrderItem>  $items
     */
    private function __construct(
        private readonly Id $id,
        private readonly Id $checkoutId,
        private readonly Id $buyerId,
        private readonly Id $sellerId,
        private readonly array $items,
        private readonly Money $itemsAmount,
        private readonly Money $deliveryAmount,
        private readonly Money $amount,
        private readonly DeliveryMethod $deliveryMethod,
        private readonly ?string $deliveryAddress,
        private ShopOrderStatus $status,
        private ?Money $commissionAmount = null,
        private ?Money $payoutAmount = null,
        private ?DateTimeImmutable $escrowHoldUntil = null,
        private ?DateTimeImmutable $buyerConfirmedAt = null,
        private ?DateTimeImmutable $sellerConfirmedAt = null,
    ) {}

    /**
     * @param  array<int, ShopOrderItem>  $items
     */
    public static function create(
        Id $id,
        Id $checkoutId,
        Id $buyerId,
        Id $sellerId,
        array $items,
        DeliveryMethod $deliveryMethod,
        ?string $deliveryAddress,
    ): self {
        $itemsAmount = Money::zero();

        foreach ($items as $item) {
            $itemsAmount = $itemsAmount->add($item->lineTotal());
        }

        $delivery = Money::fromMinorUnits($deliveryMethod->priceMinorUnits());

        return new self(
            $id,
            $checkoutId,
            $buyerId,
            $sellerId,
            $items,
            $itemsAmount,
            $delivery,
            $itemsAmount->add($delivery),
            $deliveryMethod,
            $deliveryAddress,
            ShopOrderStatus::PendingPayment,
        );
    }

    /**
     * @param  array<int, ShopOrderItem>  $items
     */
    public static function reconstitute(
        Id $id,
        Id $checkoutId,
        Id $buyerId,
        Id $sellerId,
        array $items,
        Money $itemsAmount,
        Money $deliveryAmount,
        Money $amount,
        DeliveryMethod $deliveryMethod,
        ?string $deliveryAddress,
        ShopOrderStatus $status,
        ?Money $commissionAmount,
        ?Money $payoutAmount,
        ?DateTimeImmutable $escrowHoldUntil,
        ?DateTimeImmutable $buyerConfirmedAt,
        ?DateTimeImmutable $sellerConfirmedAt,
    ): self {
        return new self(
            $id,
            $checkoutId,
            $buyerId,
            $sellerId,
            $items,
            $itemsAmount,
            $deliveryAmount,
            $amount,
            $deliveryMethod,
            $deliveryAddress,
            $status,
            $commissionAmount,
            $payoutAmount,
            $escrowHoldUntil,
            $buyerConfirmedAt,
            $sellerConfirmedAt,
        );
    }

    /**
     * Комиссия берётся только с товаров: доставку площадка не режет, она уходит целиком.
     */
    public function markPaid(int $commissionBasisPoints, int $escrowHoldDays): void
    {
        $this->assertStatus(ShopOrderStatus::PendingPayment);

        $commission = $this->itemsAmount->percentage($commissionBasisPoints);

        $this->commissionAmount = $commission;
        $this->payoutAmount = $this->amount->subtract($commission);
        $this->escrowHoldUntil = new DateTimeImmutable("+{$escrowHoldDays} days");
        $this->status = ShopOrderStatus::PaidEscrow;
    }

    public function markShipped(): void
    {
        $this->assertStatus(ShopOrderStatus::PaidEscrow);
        $this->status = ShopOrderStatus::Shipped;
    }

    public function confirmByBuyer(): void
    {
        $this->assertPaidOrShipped();
        $this->buyerConfirmedAt = new DateTimeImmutable;
        $this->completeIfBothConfirmed();
    }

    public function confirmBySeller(): void
    {
        $this->assertPaidOrShipped();
        $this->sellerConfirmedAt = new DateTimeImmutable;
        $this->completeIfBothConfirmed();
    }

    /**
     * Автоподтверждение по истечении удержания — покупатель молчит, деньги уходят продавцу.
     */
    public function autoConfirm(): void
    {
        $this->assertPaidOrShipped();
        $this->status = ShopOrderStatus::Completed;
    }

    public function openDispute(): void
    {
        $this->assertPaidOrShipped();
        $this->status = ShopOrderStatus::Disputed;
    }

    public function cancel(): void
    {
        $this->assertStatus(ShopOrderStatus::PendingPayment);
        $this->status = ShopOrderStatus::Cancelled;
    }

    public function refund(): void
    {
        $this->status = ShopOrderStatus::Refunded;
    }

    public function isBuyer(Id $userId): bool
    {
        return $this->buyerId->equals($userId);
    }

    public function isSeller(Id $userId): bool
    {
        return $this->sellerId->equals($userId);
    }

    private function completeIfBothConfirmed(): void
    {
        if ($this->buyerConfirmedAt !== null && $this->sellerConfirmedAt !== null) {
            $this->status = ShopOrderStatus::Completed;
        }
    }

    private function assertPaidOrShipped(): void
    {
        if ($this->status !== ShopOrderStatus::PaidEscrow && $this->status !== ShopOrderStatus::Shipped) {
            throw new DomainException('Действие недоступно в текущем состоянии заказа.');
        }
    }

    private function assertStatus(ShopOrderStatus $expected): void
    {
        if ($this->status !== $expected) {
            throw new DomainException('Действие недоступно в текущем состоянии заказа.');
        }
    }

    public function id(): Id
    {
        return $this->id;
    }

    /** Общий идентификатор оформления: связывает заказы, оплаченные одним платежом. */
    public function checkoutId(): Id
    {
        return $this->checkoutId;
    }

    public function buyerId(): Id
    {
        return $this->buyerId;
    }

    public function sellerId(): Id
    {
        return $this->sellerId;
    }

    /**
     * @return array<int, ShopOrderItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function itemsAmount(): Money
    {
        return $this->itemsAmount;
    }

    public function deliveryAmount(): Money
    {
        return $this->deliveryAmount;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function deliveryMethod(): DeliveryMethod
    {
        return $this->deliveryMethod;
    }

    public function deliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function status(): ShopOrderStatus
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

    public function escrowHoldUntil(): ?DateTimeImmutable
    {
        return $this->escrowHoldUntil;
    }

    public function buyerConfirmedAt(): ?DateTimeImmutable
    {
        return $this->buyerConfirmedAt;
    }

    public function sellerConfirmedAt(): ?DateTimeImmutable
    {
        return $this->sellerConfirmedAt;
    }
}
