<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Enums;

use DateTimeImmutable;

/**
 * Только лимитируемые в текущем этапе фичи — см. Context в плане подписок. Остальные ключи из
 * subscription_plans.features (who_liked_me, priority_in_feed, advanced_filters) нигде не
 * проверяются, это будущий скоуп.
 */
enum FeatureKey: string
{
    case DailyLike = 'daily_like';
    case SuperLike = 'super_like';
    case Boost = 'boost';

    /**
     * Начало окна лимита для feature_usage.period_start: сутки для лайков, неделя (с
     * понедельника) для супер-лайков, месяц для буста.
     */
    public function periodStartFor(DateTimeImmutable $now): string
    {
        return match ($this) {
            self::DailyLike => $now->format('Y-m-d'),
            self::SuperLike => $now->modify('monday this week')->format('Y-m-d'),
            self::Boost => $now->modify('first day of this month')->format('Y-m-d'),
        };
    }
}
