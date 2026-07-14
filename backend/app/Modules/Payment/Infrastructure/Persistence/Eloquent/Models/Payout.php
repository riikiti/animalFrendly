<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Payout extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'seller_id',
        'amount',
        'status',
        'yookassa_payout_id',
        'processed_at',
        'created_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
