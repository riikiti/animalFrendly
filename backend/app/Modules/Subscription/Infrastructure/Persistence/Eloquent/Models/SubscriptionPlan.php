<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plans';

    protected $fillable = [
        'slug',
        'name_ru',
        'price_amount',
        'currency',
        'period',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'bool',
    ];
}
