<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Entities;

use App\Modules\Subscription\Domain\Enums\BillingPeriod;
use App\Modules\Subscription\Domain\Enums\FeatureKey;
use App\Shared\Domain\ValueObjects\Money;

final class SubscriptionPlan
{
    private function __construct(
        private readonly int $id,
        private readonly string $slug,
        private readonly string $nameRu,
        private readonly Money $price,
        private readonly BillingPeriod $period,
        /** @var array<string, mixed> */
        private readonly array $features,
        private readonly bool $isActive,
    ) {}

    /**
     * @param  array<string, mixed>  $features
     */
    public static function reconstitute(
        int $id,
        string $slug,
        string $nameRu,
        Money $price,
        BillingPeriod $period,
        array $features,
        bool $isActive,
    ): self {
        return new self($id, $slug, $nameRu, $price, $period, $features, $isActive);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function nameRu(): string
    {
        return $this->nameRu;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function period(): BillingPeriod
    {
        return $this->period;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return array<string, mixed>
     */
    public function features(): array
    {
        return $this->features;
    }

    /**
     * null = безлимит.
     */
    public function dailyLikesLimit(): ?int
    {
        $value = $this->features['daily_likes'] ?? null;

        return $value === null ? null : (int) $value;
    }

    public function superLikesPerWeekLimit(): int
    {
        return (int) ($this->features['super_likes_per_week'] ?? 0);
    }

    public function boostsPerMonthLimit(): int
    {
        return (int) ($this->features['boosts_per_month'] ?? 0);
    }

    public function marketplaceCommissionBps(): int
    {
        return (int) ($this->features['marketplace_commission_bps'] ?? 500);
    }

    /**
     * null = безлимит.
     */
    public function limitFor(FeatureKey $key): ?int
    {
        return match ($key) {
            FeatureKey::DailyLike => $this->dailyLikesLimit(),
            FeatureKey::SuperLike => $this->superLikesPerWeekLimit(),
            FeatureKey::Boost => $this->boostsPerMonthLimit(),
        };
    }
}
