<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Entities;

use App\Modules\Payment\Domain\Enums\PaymentStatus;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

final class Payment
{
    private function __construct(
        private readonly Id $id,
        private readonly string $payableType,
        private readonly Id $payableId,
        private readonly string $idempotencyKey,
        private readonly Money $amount,
        private ?string $yookassaPaymentId,
        private PaymentStatus $status,
        /** @var array<string, mixed>|null */
        private ?array $rawPayload,
    ) {}

    public static function create(Id $id, string $payableType, Id $payableId, Money $amount, string $idempotencyKey): self
    {
        return new self($id, $payableType, $payableId, $idempotencyKey, $amount, null, PaymentStatus::Pending, null);
    }

    /**
     * @param  array<string, mixed>|null  $rawPayload
     */
    public static function reconstitute(
        Id $id,
        string $payableType,
        Id $payableId,
        string $idempotencyKey,
        Money $amount,
        ?string $yookassaPaymentId,
        PaymentStatus $status,
        ?array $rawPayload,
    ): self {
        return new self($id, $payableType, $payableId, $idempotencyKey, $amount, $yookassaPaymentId, $status, $rawPayload);
    }

    public function attachYookassaId(string $yookassaPaymentId): void
    {
        $this->yookassaPaymentId = $yookassaPaymentId;
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function markSucceeded(array $rawPayload): void
    {
        $this->status = PaymentStatus::Succeeded;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function markCanceled(array $rawPayload): void
    {
        $this->status = PaymentStatus::Canceled;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function markRefunded(array $rawPayload): void
    {
        $this->status = PaymentStatus::Refunded;
        $this->rawPayload = $rawPayload;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [PaymentStatus::Succeeded, PaymentStatus::Canceled, PaymentStatus::Refunded], true);
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function payableType(): string
    {
        return $this->payableType;
    }

    public function payableId(): Id
    {
        return $this->payableId;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function yookassaPaymentId(): ?string
    {
        return $this->yookassaPaymentId;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function rawPayload(): ?array
    {
        return $this->rawPayload;
    }
}
