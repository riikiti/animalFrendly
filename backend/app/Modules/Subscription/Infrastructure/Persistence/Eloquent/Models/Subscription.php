<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Subscription extends Model
{
    protected $table = 'subscriptions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'plan_id',
        'status',
        'started_at',
        'current_period_ends_at',
        'auto_renew',
        'canceled_at',
        'yookassa_payment_method_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'auto_renew' => 'bool',
        'canceled_at' => 'datetime',
    ];
}
