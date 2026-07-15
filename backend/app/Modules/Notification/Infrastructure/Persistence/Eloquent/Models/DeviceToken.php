<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class DeviceToken extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'platform',
        'fcm_token',
        'created_at',
        'last_used_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];
}
