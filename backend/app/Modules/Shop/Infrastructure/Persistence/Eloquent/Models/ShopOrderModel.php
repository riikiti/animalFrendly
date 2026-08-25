<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ShopOrderModel extends Model
{
    use HasUlids;

    protected $table = 'shop_orders';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'checkout_id',
        'buyer_id',
        'seller_id',
        'status',
        'items_amount',
        'delivery_amount',
        'amount',
        'commission_amount',
        'payout_amount',
        'currency',
        'delivery_method',
        'delivery_address',
        'escrow_hold_until',
        'buyer_confirmed_at',
        'seller_confirmed_at',
    ];

    protected $casts = [
        'items_amount' => 'integer',
        'delivery_amount' => 'integer',
        'amount' => 'integer',
        'commission_amount' => 'integer',
        'payout_amount' => 'integer',
        'escrow_hold_until' => 'datetime',
        'buyer_confirmed_at' => 'datetime',
        'seller_confirmed_at' => 'datetime',
    ];

    /**
     * @return HasMany<ShopOrderItemModel, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItemModel::class, 'order_id');
    }
}
