<?php

declare(strict_types=1);

namespace Database\Seeders\Subscription;

use App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Тарифы заводятся сидером, не через API — Admin-модуля для CRUD тарифов пока нет (см. Context
 * в docs/plan/09-flow-subscriptions.md-плане). Цены Plus/Premium — условные плейсхолдеры,
 * реальный прайсинг определит бизнес.
 */
final class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free',
                'name_ru' => 'Free',
                'price_amount' => 0,
                'period' => 'month',
                'features' => [
                    'daily_likes' => 20,
                    'super_likes_per_week' => 1,
                    'boosts_per_month' => 0,
                    'marketplace_commission_bps' => 500,
                    'who_liked_me' => false,
                    'priority_in_feed' => false,
                    'advanced_filters' => false,
                ],
            ],
            [
                'slug' => 'plus',
                'name_ru' => 'Plus',
                'price_amount' => 29900,
                'period' => 'month',
                'features' => [
                    'daily_likes' => null,
                    'super_likes_per_week' => 5,
                    'boosts_per_month' => 1,
                    'marketplace_commission_bps' => 500,
                    'who_liked_me' => false,
                    'priority_in_feed' => false,
                    'advanced_filters' => true,
                ],
            ],
            [
                'slug' => 'premium',
                'name_ru' => 'Premium',
                'price_amount' => 59900,
                'period' => 'month',
                'features' => [
                    'daily_likes' => null,
                    'super_likes_per_week' => 15,
                    'boosts_per_month' => 4,
                    'marketplace_commission_bps' => 400,
                    'who_liked_me' => true,
                    'priority_in_feed' => true,
                    'advanced_filters' => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name_ru' => $plan['name_ru'],
                    'price_amount' => $plan['price_amount'],
                    'currency' => 'RUB',
                    'period' => $plan['period'],
                    'features' => $plan['features'],
                    'is_active' => true,
                ],
            );
        }
    }
}
