<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Entities;

use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Exceptions\InvalidSubscriptionStatusTransitionException;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

/**
 * State machine подписки — единственное место в кодовой базе, где меняется subscriptions.status.
 * См. docs/plan/09-flow-subscriptions.md и docs/database/06-subscription.md.
 */
final class Subscription
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $userId,
        private readonly int $planId,
        private SubscriptionStatus $status,
        private ?DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $currentPeriodEndsAt,
        private bool $autoRenew,
        private ?DateTimeImmutable $canceledAt,
        private ?string $yookassaPaymentMethodId,
    ) {}

    public static function subscribe(Id $id, Id $userId, int $planId): self
    {
        return new self($id, $userId, $planId, SubscriptionStatus::PendingPayment, null, null, true, null, null);
    }

    public static function reconstitute(
        Id $id,
        Id $userId,
        int $planId,
        SubscriptionStatus $status,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $currentPeriodEndsAt,
        bool $autoRenew,
        ?DateTimeImmutable $canceledAt,
        ?string $yookassaPaymentMethodId,
    ): self {
        return new self(
            $id, $userId, $planId, $status, $startedAt, $currentPeriodEndsAt,
            $autoRenew, $canceledAt, $yookassaPaymentMethodId,
        );
    }

    /**
     * Первая (или повторная после past_due) успешная оплата.
     */
    public function activate(DateTimeImmutable $periodEnd, ?string $paymentMethodId): void
    {
        $this->assertStatusIn([SubscriptionStatus::PendingPayment, SubscriptionStatus::PastDue]);

        $this->status = SubscriptionStatus::Active;
        $this->startedAt ??= new DateTimeImmutable;
        $this->currentPeriodEndsAt = $periodEnd;

        if ($paymentMethodId !== null) {
            $this->yookassaPaymentMethodId = $paymentMethodId;
        }
    }

    /**
     * Успешное автосписание за очередной период.
     */
    public function renew(DateTimeImmutable $newPeriodEnd): void
    {
        $this->assertStatusIn([SubscriptionStatus::Active]);
        $this->currentPeriodEndsAt = $newPeriodEnd;
    }

    /**
     * Отказ автосписания — доступ сохраняется на время grace-периода.
     */
    public function markPastDue(): void
    {
        $this->assertStatusIn([SubscriptionStatus::Active]);
        $this->status = SubscriptionStatus::PastDue;
    }

    public function expire(): void
    {
        $this->assertStatusIn([
            SubscriptionStatus::PendingPayment,
            SubscriptionStatus::PastDue,
            SubscriptionStatus::Active,
        ]);
        $this->status = SubscriptionStatus::Expired;
    }

    /**
     * Отключает автопродление — доступ остаётся до конца оплаченного периода
     * (переход в Expired сделает billing-джоба, когда период истечёт).
     */
    public function cancelAutoRenew(): void
    {
        $this->assertStatusIn([SubscriptionStatus::Active]);
        $this->autoRenew = false;
        $this->canceledAt = new DateTimeImmutable;
    }

    /**
     * @param  list<SubscriptionStatus>  $expected
     */
    private function assertStatusIn(array $expected): void
    {
        if (! in_array($this->status, $expected, true)) {
            throw InvalidSubscriptionStatusTransitionException::create();
        }
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function userId(): Id
    {
        return $this->userId;
    }

    public function planId(): int
    {
        return $this->planId;
    }

    public function status(): SubscriptionStatus
    {
        return $this->status;
    }

    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function currentPeriodEndsAt(): ?DateTimeImmutable
    {
        return $this->currentPeriodEndsAt;
    }

    public function autoRenew(): bool
    {
        return $this->autoRenew;
    }

    public function canceledAt(): ?DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function yookassaPaymentMethodId(): ?string
    {
        return $this->yookassaPaymentMethodId;
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }
}
