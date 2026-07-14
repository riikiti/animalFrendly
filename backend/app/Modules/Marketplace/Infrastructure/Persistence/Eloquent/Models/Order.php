<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class Order extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'listing_id',
        'buyer_id',
        'seller_id',
        'amount',
        'currency',
        'commission_amount',
        'payout_amount',
        'status',
        'buyer_confirmed_at',
        'seller_confirmed_at',
        'escrow_hold_until',
    ];

    protected $casts = [
        'buyer_confirmed_at' => 'datetime',
        'seller_confirmed_at' => 'datetime',
        'escrow_hold_until' => 'datetime',
    ];
}
