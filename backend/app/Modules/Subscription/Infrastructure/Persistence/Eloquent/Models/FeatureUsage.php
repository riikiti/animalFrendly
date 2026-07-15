<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class FeatureUsage extends Model
{
    protected $table = 'feature_usage';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'feature_key',
        'period_start',
        'used_count',
    ];
}
