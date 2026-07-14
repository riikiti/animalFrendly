<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Entities;

use App\Modules\Payment\Domain\Enums\PayoutStatus;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;
use DateTimeImmutable;

final class Payout
{
    private function __construct(
        private readonly Id $id,
        private readonly Id $orderId,
        private readonly Id $sellerId,
        private readonly Money $amount,
        private PayoutStatus $status,
        private ?string $yookassaPayoutId,
        private ?DateTimeImmutable $processedAt,
    ) {}

    public static function create(Id $id, Id $orderId, Id $sellerId, Money $amount): self
    {
        return new self($id, $orderId, $sellerId, $amount, PayoutStatus::Pending, null, null);
    }

    public static function reconstitute(
        Id $id,
        Id $orderId,
        Id $sellerId,
        Money $amount,
        PayoutStatus $status,
        ?string $yookassaPayoutId,
        ?DateTimeImmutable $processedAt,
    ): self {
        return new self($id, $orderId, $sellerId, $amount, $status, $yookassaPayoutId, $processedAt);
    }

    public function markProcessing(): void
    {
        $this->status = PayoutStatus::Processing;
    }

    public function markPaid(string $yookassaPayoutId): void
    {
        $this->status = PayoutStatus::Paid;
        $this->yookassaPayoutId = $yookassaPayoutId;
        $this->processedAt = new DateTimeImmutable;
    }

    public function markFailed(): void
    {
        $this->status = PayoutStatus::Failed;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function orderId(): Id
    {
        return $this->orderId;
    }

    public function sellerId(): Id
    {
        return $this->sellerId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): PayoutStatus
    {
        return $this->status;
    }

    public function yookassaPayoutId(): ?string
    {
        return $this->yookassaPayoutId;
    }

    public function processedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }
}
