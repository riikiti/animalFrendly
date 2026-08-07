<?php

declare(strict_types=1);

namespace App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ShopOrderItemModel extends Model
{
    use HasUlids;

    protected $table = 'shop_order_items';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'order_id', 'product_id', 'title', 'price_amount', 'quantity'];

    protected $casts = ['price_amount' => 'integer', 'quantity' => 'integer'];
}
